#!/bin/bash
# Development environment setup

set -e

echo "🚀 Setting up development environment..."

# Start Docker services
echo "📦 Starting Docker services..."
docker-compose up -d

# Wait for services
echo "⏳ Waiting for services to be ready..."
sleep 5

# Install backend dependencies
echo "📥 Installing backend dependencies..."
docker exec backend-php83 composer install

# Run migrations
echo "🔄 Running database migrations..."
docker exec backend-php83 php bin/console doctrine:migrations:migrate --no-interaction

# Install frontend dependencies
echo "📥 Installing frontend dependencies..."
cd apps/frontend
npm install
cd ../..

echo "✅ Development environment ready!"
echo "Backend API: http://localhost:8089"
echo "Frontend: Run 'cd apps/frontend && npm run dev'"
