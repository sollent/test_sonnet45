#!/bin/bash

# Health Check Script for TaskFlow Application
# This script performs comprehensive health checks on all application components

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
FRONTEND_URL="${FRONTEND_URL:-https://task.nesty.by}"
API_URL="${API_URL:-https://api.task.nesty.by}"
MAX_RETRIES="${MAX_RETRIES:-30}"
RETRY_DELAY="${RETRY_DELAY:-10}"

# Exit codes
EXIT_SUCCESS=0
EXIT_FRONTEND_FAIL=1
EXIT_API_FAIL=2
EXIT_DB_FAIL=3
EXIT_RABBITMQ_FAIL=4
EXIT_CONTAINER_FAIL=5

echo "🏥 Starting health checks for TaskFlow application..."
echo "================================================"

# Function to check HTTP endpoint
check_http() {
    local url=$1
    local name=$2
    local max_attempts=$3
    local attempt=0

    echo -n "Checking $name ($url)... "

    while [ $attempt -lt $max_attempts ]; do
        if curl -f -s -o /dev/null -w "%{http_code}" "$url" | grep -q "200\|301\|302"; then
            echo -e "${GREEN}✅ OK${NC}"
            return 0
        fi

        attempt=$((attempt + 1))
        if [ $attempt -lt $max_attempts ]; then
            sleep $RETRY_DELAY
        fi
    done

    echo -e "${RED}❌ FAILED${NC}"
    return 1
}

# Function to check container health
check_container() {
    local container=$1
    local name=$2

    echo -n "Checking $name container ($container)... "

    if docker ps --format "table {{.Names}}\t{{.Status}}" | grep -q "$container.*Up.*healthy"; then
        echo -e "${GREEN}✅ Healthy${NC}"
        return 0
    elif docker ps --format "table {{.Names}}" | grep -q "$container"; then
        echo -e "${YELLOW}⚠️ Running but not healthy${NC}"
        return 0
    else
        echo -e "${RED}❌ Not running${NC}"
        return 1
    fi
}

# Function to check PostgreSQL
check_postgres() {
    echo -n "Checking PostgreSQL connection... "

    if docker exec backend-php83 php -r "
        try {
            \$pdo = new PDO('postgresql:host=psql16;port=5432;dbname=backend_prod', 'prod_user', getenv('POSTGRES_PASSWORD'));
            echo 'OK';
            exit(0);
        } catch (Exception \$e) {
            echo 'FAILED: ' . \$e->getMessage();
            exit(1);
        }
    " 2>/dev/null | grep -q "OK"; then
        echo -e "${GREEN}✅ Connected${NC}"
        return 0
    else
        echo -e "${RED}❌ Connection failed${NC}"
        return 1
    fi
}

# Function to check RabbitMQ
check_rabbitmq() {
    echo -n "Checking RabbitMQ... "

    if docker exec backend-rabbitmq rabbitmqctl status 2>/dev/null | grep -q "Uptime"; then
        echo -e "${GREEN}✅ Running${NC}"
        return 0
    else
        echo -e "${RED}❌ Not running${NC}"
        return 1
    fi
}

# Function to run smoke tests
run_smoke_tests() {
    echo ""
    echo "🧪 Running smoke tests..."
    echo "-------------------------"

    local all_passed=true

    # Test login endpoint
    echo -n "Testing login endpoint... "
    response=$(curl -s -X POST "$API_URL/api/login" \
        -H "Content-Type: application/json" \
        -d '{"email":"test@example.com","password":"test"}' \
        -w "\n%{http_code}" 2>/dev/null || echo "000")

    http_code=$(echo "$response" | tail -n 1)
    if [ "$http_code" = "401" ] || [ "$http_code" = "400" ]; then
        echo -e "${GREEN}✅ Working (auth failure expected)${NC}"
    else
        echo -e "${RED}❌ Not working (HTTP $http_code)${NC}"
        all_passed=false
    fi

    # Test tasks endpoint (should require auth)
    echo -n "Testing tasks endpoint security... "
    response=$(curl -s "$API_URL/api/tasks" -w "\n%{http_code}" 2>/dev/null || echo "000")
    http_code=$(echo "$response" | tail -n 1)
    if [ "$http_code" = "401" ]; then
        echo -e "${GREEN}✅ Properly secured${NC}"
    else
        echo -e "${RED}❌ Not secured (HTTP $http_code)${NC}"
        all_passed=false
    fi

    # Test health endpoint
    echo -n "Testing health endpoint... "
    if curl -f -s "$API_URL/api/health" 2>/dev/null | grep -q "ok"; then
        echo -e "${GREEN}✅ Healthy${NC}"
    else
        echo -e "${RED}❌ Not healthy${NC}"
        all_passed=false
    fi

    if [ "$all_passed" = true ]; then
        return 0
    else
        return 1
    fi
}

# Main health check execution
main() {
    local overall_status=0

    echo ""
    echo "🌐 HTTP Endpoints"
    echo "-----------------"
    if ! check_http "$FRONTEND_URL" "Frontend" "$MAX_RETRIES"; then
        overall_status=$EXIT_FRONTEND_FAIL
    fi

    if ! check_http "$API_URL/api" "Backend API" "$MAX_RETRIES"; then
        overall_status=$EXIT_API_FAIL
    fi

    echo ""
    echo "🐳 Docker Containers"
    echo "--------------------"
    check_container "backend-nginx" "Nginx" || overall_status=$EXIT_CONTAINER_FAIL
    check_container "backend-php83" "PHP-FPM" || overall_status=$EXIT_CONTAINER_FAIL
    check_container "backend-psql16" "PostgreSQL" || overall_status=$EXIT_CONTAINER_FAIL
    check_container "backend-rabbitmq" "RabbitMQ" || overall_status=$EXIT_CONTAINER_FAIL
    check_container "backend-cron" "Cron" || overall_status=$EXIT_CONTAINER_FAIL

    echo ""
    echo "💾 Services"
    echo "-----------"
    check_postgres || overall_status=$EXIT_DB_FAIL
    check_rabbitmq || overall_status=$EXIT_RABBITMQ_FAIL

    # Run smoke tests only if basic checks pass
    if [ $overall_status -eq 0 ]; then
        run_smoke_tests || overall_status=6
    else
        echo ""
        echo "⚠️ Skipping smoke tests due to failed basic checks"
    fi

    echo ""
    echo "================================================"

    if [ $overall_status -eq 0 ]; then
        echo -e "${GREEN}✅ All health checks passed!${NC}"
        echo ""
        echo "Application URLs:"
        echo "  Frontend: $FRONTEND_URL"
        echo "  API: $API_URL"
    else
        echo -e "${RED}❌ Some health checks failed!${NC}"
        echo "Exit code: $overall_status"
        echo ""
        echo "Troubleshooting:"
        echo "  1. Check container logs: docker logs backend-php83"
        echo "  2. Check container status: docker ps"
        echo "  3. Check database: docker exec backend-php83 php bin/console doctrine:migrations:status"
        echo "  4. Check nginx: docker logs backend-nginx"
    fi

    exit $overall_status
}

# Handle script arguments
case "${1:-}" in
    --help|-h)
        echo "Usage: $0 [OPTIONS]"
        echo ""
        echo "Options:"
        echo "  -h, --help     Show this help message"
        echo "  --quick        Run quick checks only (no retries)"
        echo "  --verbose      Show detailed output"
        echo ""
        echo "Environment variables:"
        echo "  FRONTEND_URL   Frontend URL (default: https://task.nesty.by)"
        echo "  API_URL        API URL (default: https://api.task.nesty.by)"
        echo "  MAX_RETRIES    Maximum retry attempts (default: 30)"
        echo "  RETRY_DELAY    Delay between retries in seconds (default: 10)"
        exit 0
        ;;
    --quick)
        MAX_RETRIES=1
        RETRY_DELAY=1
        ;;
    --verbose)
        set -x
        ;;
esac

# Run main function
main