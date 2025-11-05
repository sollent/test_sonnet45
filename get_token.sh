#!/bin/bash

# Get JWT token for testing

EMAIL="vladislikedev@gmail.com"
PASSWORD="Pahan1998"

echo "Getting JWT token..."

RESPONSE=$(curl -s -X POST "http://localhost:8089/api/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

TOKEN=$(echo "$RESPONSE" | python3 -c "import sys, json; print(json.load(sys.stdin).get('token', 'ERROR'))" 2>/dev/null)

if [ "$TOKEN" = "ERROR" ] || [ -z "$TOKEN" ]; then
  echo "Failed to get token. Response:"
  echo "$RESPONSE" | python3 -m json.tool 2>&1
  exit 1
fi

echo ""
echo "✅ Token obtained successfully!"
echo ""
echo "TOKEN=$TOKEN"
echo ""
echo "You can use it with:"
echo "curl -H \"Authorization: Bearer $TOKEN\" http://localhost:8089/api/tasks"
