#!/bin/bash
# Check health of all services

echo "🏥 Checking service health..."

# Check Docker containers
echo ""
echo "📦 Docker Containers:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Check backend API
echo ""
echo "🔧 Backend API:"
curl -s http://localhost:8089 >/dev/null 2>&1 && echo "✅ Backend API OK" || echo "❌ Backend API not responding"

# Check PostgreSQL
echo ""
echo "🐘 PostgreSQL:"
docker exec backend-psql16 psql -U user -d backend-app -c "SELECT version();" > /dev/null 2>&1 && echo "✅ PostgreSQL OK" || echo "❌ PostgreSQL error"

# Check RabbitMQ
echo ""
echo "🐰 RabbitMQ:"
curl -s http://localhost:15672 > /dev/null && echo "✅ RabbitMQ OK" || echo "❌ RabbitMQ error"

echo ""
echo "✅ Health check complete!"
