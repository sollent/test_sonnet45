#!/bin/bash
# Production Diagnostics Script
# Проверяет статус всех сервисов после деплоя

set -e

echo "========================================"
echo "🔍 PRODUCTION DIAGNOSTICS"
echo "========================================"
echo ""

cd /var/www/test_sonnet45

echo "📋 1. Checking Docker containers status..."
echo "----------------------------------------"
docker ps -a --filter "name=test_sonnet45" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""

echo "🌐 2. Checking ports in use..."
echo "----------------------------------------"
echo "Port 80 (Host Nginx):"
lsof -i:80 || echo "  No process listening on port 80"
echo ""
echo "Port 8089 (Docker Backend Nginx):"
lsof -i:8089 || echo "  No process listening on port 8089"
echo ""
echo "Port 8000 (Docker Frontend):"
lsof -i:8000 || echo "  No process listening on port 8000"
echo ""

echo "📦 3. Checking Docker Compose configuration..."
echo "----------------------------------------"
docker compose -f docker-compose.yml \
              -f infrastructure/docker/docker-compose-prod.yml \
              -f infrastructure/docker/docker-compose.frontend-prod.yml \
              ps
echo ""

echo "📝 4. Checking environment files..."
echo "----------------------------------------"
echo ".env.docker.prod exists:"
ls -lh .env.docker.prod 2>/dev/null || echo "  ❌ FILE NOT FOUND"
echo ""
echo "apps/backend/.env.prod exists:"
ls -lh apps/backend/.env.prod 2>/dev/null || echo "  ❌ FILE NOT FOUND"
echo ""
echo ".env.docker symlink:"
ls -lh .env.docker 2>/dev/null || echo "  ❌ SYMLINK NOT FOUND"
echo ""
echo "apps/backend/.env symlink:"
ls -lh apps/backend/.env 2>/dev/null || echo "  ❌ SYMLINK NOT FOUND"
echo ""

echo "🔧 5. Testing container connectivity..."
echo "----------------------------------------"
echo "Backend PHP container:"
docker exec test_sonnet45-php83 php -v 2>/dev/null || echo "  ❌ Cannot execute in PHP container"
echo ""
echo "PostgreSQL container:"
docker exec test_sonnet45-psql16 psql -U prod_user -d backend_prod -c "SELECT 1;" 2>/dev/null || echo "  ❌ Cannot connect to PostgreSQL"
echo ""

echo "📊 6. Checking container logs (last 20 lines)..."
echo "----------------------------------------"
echo "=== Backend Nginx logs ==="
docker logs --tail 20 test_sonnet45-nginx 2>&1 || echo "  ❌ Cannot get nginx logs"
echo ""
echo "=== Backend PHP logs ==="
docker logs --tail 20 test_sonnet45-php83 2>&1 || echo "  ❌ Cannot get PHP logs"
echo ""
echo "=== Frontend logs ==="
docker logs --tail 20 frontend-prod 2>&1 || echo "  ❌ Cannot get frontend logs"
echo ""

echo "🌐 7. Testing local HTTP endpoints..."
echo "----------------------------------------"
echo "Testing http://localhost:8089 (Backend API via Docker):"
curl -s -o /dev/null -w "  HTTP Status: %{http_code}\n" http://localhost:8089 2>/dev/null || echo "  ❌ Connection failed"
echo ""
echo "Testing http://localhost:8000 (Frontend via Docker):"
curl -s -o /dev/null -w "  HTTP Status: %{http_code}\n" http://localhost:8000 2>/dev/null || echo "  ❌ Connection failed"
echo ""

echo "🔐 8. Checking host nginx configuration..."
echo "----------------------------------------"
echo "Frontend nginx config (/etc/nginx/sites-enabled/task.nesty.by):"
if [ -f /etc/nginx/sites-enabled/task.nesty.by ]; then
  grep -A 5 "proxy_pass" /etc/nginx/sites-enabled/task.nesty.by | head -10
else
  echo "  ❌ Config file not found"
fi
echo ""
echo "Backend API nginx config (/etc/nginx/sites-enabled/api.task.nesty.by):"
if [ -f /etc/nginx/sites-enabled/api.task.nesty.by ]; then
  grep -A 5 "proxy_pass" /etc/nginx/sites-enabled/api.task.nesty.by | head -10
else
  echo "  ❌ Config file not found"
fi
echo ""

echo "✅ 9. Host nginx status..."
echo "----------------------------------------"
systemctl status nginx --no-pager -l || echo "  ❌ Nginx service not running"
echo ""

echo "========================================"
echo "🏁 DIAGNOSTICS COMPLETE"
echo "========================================"
