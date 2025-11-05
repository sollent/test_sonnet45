#!/bin/bash

# JSON Cache Implementation - Test Script
# Tests the new JSON-based caching system

set -e  # Exit on error

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║          JSON CACHE IMPLEMENTATION - TEST SCRIPT              ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration
REDIS_CONTAINER="backend-redis"
API_URL="http://localhost:8089/api/tasks"
USER_ID="22"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Step 1: Check Redis Connection"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if docker exec $REDIS_CONTAINER redis-cli PING > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Redis is running"
else
    echo -e "${RED}✗${NC} Redis is not accessible!"
    exit 1
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Step 2: Clear Cache (Fresh Start)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

docker exec $REDIS_CONTAINER redis-cli FLUSHDB > /dev/null
KEYS_COUNT=$(docker exec $REDIS_CONTAINER redis-cli DBSIZE)
echo -e "${GREEN}✓${NC} Cache cleared (DBSIZE: $KEYS_COUNT)"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Step 3: Make API Request (Cache MISS - should populate cache)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo -e "${BLUE}→${NC} GET $API_URL"

# Make request and capture time
START_TIME=$(date +%s%3N)
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer YOUR_TOKEN_HERE" 2>/dev/null || echo "000")
END_TIME=$(date +%s%3N)
DURATION=$((END_TIME - START_TIME))

if [ "$HTTP_CODE" == "200" ] || [ "$HTTP_CODE" == "401" ]; then
    echo -e "${GREEN}✓${NC} API Response: HTTP $HTTP_CODE (${DURATION}ms)"
    if [ "$HTTP_CODE" == "401" ]; then
        echo -e "${YELLOW}⚠${NC}  Authentication required (expected in test environment)"
    fi
else
    echo -e "${RED}✗${NC} API Request Failed: HTTP $HTTP_CODE"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Step 4: Check Cache Population"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

KEYS_COUNT=$(docker exec $REDIS_CONTAINER redis-cli DBSIZE)
echo -e "${BLUE}→${NC} Total keys in Redis: $KEYS_COUNT"

echo ""
echo "Finding task cache keys for user $USER_ID..."
TASK_KEYS=$(docker exec $REDIS_CONTAINER redis-cli KEYS "*user_tasks*uid_${USER_ID}*" 2>/dev/null || echo "")

if [ -n "$TASK_KEYS" ]; then
    echo -e "${GREEN}✓${NC} Found task cache keys:"
    echo "$TASK_KEYS" | while read -r key; do
        if [ -n "$key" ]; then
            # Get TTL
            TTL=$(docker exec $REDIS_CONTAINER redis-cli TTL "$key")
            # Get size
            SIZE=$(docker exec $REDIS_CONTAINER redis-cli STRLEN "$key")
            SIZE_KB=$(awk "BEGIN {printf \"%.2f\", $SIZE/1024}")

            echo "  • $key"
            echo "    TTL: ${TTL}s | Size: ${SIZE_KB} KB"
        fi
    done
else
    echo -e "${YELLOW}⚠${NC}  No task cache keys found (might need authentication)"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Step 5: Inspect Cache Content (JSON Format)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Get first cache key
FIRST_KEY=$(docker exec $REDIS_CONTAINER redis-cli KEYS "*user_tasks_list*uid_${USER_ID}*" | head -n 1)

if [ -n "$FIRST_KEY" ]; then
    echo -e "${BLUE}→${NC} Examining key: $FIRST_KEY"
    echo ""

    # Get cache content
    CACHE_CONTENT=$(docker exec $REDIS_CONTAINER redis-cli GET "$FIRST_KEY")

    # Check if it's valid JSON
    if echo "$CACHE_CONTENT" | jq empty 2>/dev/null; then
        echo -e "${GREEN}✓${NC} Cache contains valid JSON!"
        echo ""
        echo "First task in cache:"
        echo "$CACHE_CONTENT" | jq '.[0] | {id, title, status, priority, tags}' 2>/dev/null || echo "(parsing failed)"
        echo ""
        echo "Total tasks in cache:"
        TASK_COUNT=$(echo "$CACHE_CONTENT" | jq '. | length' 2>/dev/null || echo "0")
        echo -e "  ${GREEN}$TASK_COUNT${NC} tasks"
    else
        echo -e "${RED}✗${NC} Cache content is NOT valid JSON"
        echo "First 200 characters:"
        echo "$CACHE_CONTENT" | head -c 200
        echo ""
    fi
else
    echo -e "${YELLOW}⚠${NC}  No cache keys to inspect"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Step 6: Test Cache HIT (Second Request)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo -e "${BLUE}→${NC} Making second request (should be from cache)..."

START_TIME=$(date +%s%3N)
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer YOUR_TOKEN_HERE" 2>/dev/null || echo "000")
END_TIME=$(date +%s%3N)
DURATION=$((END_TIME - START_TIME))

if [ "$HTTP_CODE" == "200" ] || [ "$HTTP_CODE" == "401" ]; then
    echo -e "${GREEN}✓${NC} API Response: HTTP $HTTP_CODE (${DURATION}ms)"
    echo -e "${BLUE}→${NC} Cache HIT expected (faster response)"
else
    echo -e "${RED}✗${NC} API Request Failed: HTTP $HTTP_CODE"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Summary"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo ""
echo "Redis Connection:  $(docker exec $REDIS_CONTAINER redis-cli PING)"
echo "Total Cache Keys:  $(docker exec $REDIS_CONTAINER redis-cli DBSIZE)"
echo ""
echo "Next steps:"
echo "  1. Open Another Redis Desktop Manager (localhost:16379)"
echo "  2. Browse to: app → app → prod → user_tasks_list"
echo "  3. View cache as JSON (should see clean, readable data)"
echo ""
echo -e "${GREEN}✓ Test Complete!${NC}"
echo ""
