#!/bin/bash
# Reset database (WARNING: deletes all data!)

set -e

echo "⚠️  WARNING: This will delete ALL database data!"
read -p "Are you sure? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "❌ Aborted"
    exit 1
fi

echo "🗑️  Dropping database..."
docker exec backend-php83 php bin/console doctrine:database:drop --force --if-exists

echo "🔨 Creating database..."
docker exec backend-php83 php bin/console doctrine:database:create

echo "🔄 Running migrations..."
docker exec backend-php83 php bin/console doctrine:migrations:migrate --no-interaction

echo "✅ Database reset complete!"
