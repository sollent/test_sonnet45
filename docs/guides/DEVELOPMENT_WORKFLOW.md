# 🛠 Development Workflow - Daily Development Guide

> **TL;DR**: Docker setup for backend (Symfony + PostgreSQL) via `docker-compose.yml` in root, npm for frontend (Vue + Vite). Database migrations with Doctrine. Git workflow with feature branches.

---

## 📋 Project Structure

```
test_sonnet45/
├── docker-compose.yml              # Main Docker Compose (includes infrastructure configs)
├── Makefile                        # Common commands
├── apps/
│   ├── backend/                    # Symfony application
│   │   ├── src/                    # PHP source code
│   │   ├── config/                 # Configuration files
│   │   └── ...
│   └── frontend/                   # Vue.js application
│       ├── src/                    # TypeScript source code
│       └── ...
├── infrastructure/
│   ├── docker/                     # Docker configuration
│   │   ├── docker-compose.app.yml  # App services
│   │   ├── docker-compose.ai.yml   # AI services (placeholder)
│   │   ├── docker-compose.dev.yml  # Dev overrides
│   │   ├── dev/
│   │   │   ├── nginx/              # Nginx configuration
│   │   │   └── php/                # PHP-FPM configuration
│   │   └── cron/                   # Cron jobs
│   └── ai-services/                # AI infrastructure (placeholder)
└── scripts/                        # Utility scripts
```

---

## First-Time Setup

### 1. Clone Repository

```bash
git clone <repository-url>
cd test_sonnet45
```

### 2. Backend Setup (Docker)

**IMPORTANT**: Docker configuration is `docker-compose.yml` in project root

```bash
cd apps/backend

# Copy environment file
cp .env .env.local

# Configure .env.local
DATABASE_URL="postgresql://user:password@psql16:5432/backend-app"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret

# Generate JWT keys
mkdir -p config/jwt
openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

# Start Docker services (from project root)
cd ../..
docker-compose up -d

# Install dependencies
docker exec backend-php83 composer install

# Run migrations
docker exec backend-php83 php bin/console doctrine:migrations:migrate

# (Optional) Load fixtures
docker exec backend-php83 php bin/console doctrine:fixtures:load
```

### 3. Frontend Setup

```bash
cd apps/frontend

# Install dependencies
npm install

# Copy environment file (if needed)
cp .env.example .env

# Configure .env
VITE_API_BASE_URL=http://localhost:8089
VITE_GOOGLE_CLIENT_ID=your-google-client-id

# Start dev server
npm run dev
```

### 4. Access Application

- **Frontend:** http://localhost:3000 (Vite dev server)
- **Backend API:** http://localhost:8089/api (Nginx)
- **PostgreSQL:** localhost:15432 (external port)
- **RabbitMQ Management:** http://localhost:15672 (user/password)

### 5. Install Git Hooks (Recommended)

```bash
# Install pre-commit hooks for code quality checks
bash scripts/install-git-hooks.sh
```

This installs Git hooks that automatically run:
- **PHP-CS-Fixer** - Code style checks (PSR-12)
- **PHPStan** - Static analysis (level 5)

Hooks run before each commit and block commits with issues.

---

## Daily Development

### Starting Services

```bash
# Start backend (from docker/ directory)
cd docker
docker-compose up -d

# Or from anywhere in project:
docker-compose -f docker/docker-compose.yml up -d

# Start frontend (from frontend/ directory)
cd frontend
npm run dev
```

### Stopping Services

```bash
# Stop backend (from docker/ directory)
cd docker
docker-compose down

# Or from anywhere:
docker-compose -f docker/docker-compose.yml down

# Stop frontend
Ctrl+C in terminal
```

### Rebuilding Docker Containers

```bash
# Rebuild all containers (when Dockerfile changes)
cd docker
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Rebuild specific service
docker-compose build --no-cache php83-fpm
docker-compose up -d php83-fpm
```

### Viewing Docker Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f php83-fpm
docker-compose logs -f nginx
docker-compose logs -f psql16
```

---

## Database Operations

### Create Migration

```bash
# Auto-generate migration from entity changes
docker exec backend-php83 php bin/console make:migration

# Review migration file in migrations/
# Then run migration
docker exec backend-php83 php bin/console doctrine:migrations:migrate
```

### Rollback Migration

```bash
docker exec backend-php83 php bin/console doctrine:migrations:migrate prev
```

### Create Entity

```bash
docker exec backend-php83 php bin/console make:entity
```

---

## Docker Container Operations

### Managing Containers

```bash
# List all running containers
docker ps

# List all containers (including stopped)
docker ps -a

# Check container logs
docker logs backend-php83
docker logs -f backend-nginx     # Follow logs in real-time
docker logs --tail 100 backend-php83  # Last 100 lines

# Restart specific container
docker restart backend-php83

# Stop specific container
docker stop backend-php83

# Start specific container
docker start backend-php83

# Remove stopped containers
docker rm backend-php83
```

### Accessing Containers

```bash
# Execute commands in containers
docker exec backend-php83 php --version
docker exec backend-php83 composer --version

# Interactive shell access
docker exec -it backend-php83 bash
docker exec -it backend-psql16 bash
```

### PostgreSQL Database Operations

```bash
# Connect to PostgreSQL
docker exec -it backend-psql16 psql -U user -d backend-app

# Common PostgreSQL commands (inside psql)
\dt              # List tables
\d+ tasks        # Describe tasks table
\l               # List databases
\q               # Quit

# Execute SQL from host
docker exec backend-psql16 psql -U user -d backend-app -c "SELECT COUNT(*) FROM tasks;"

# Backup database
docker exec backend-psql16 pg_dump -U user backend-app > backup.sql

# Restore database
docker exec -i backend-psql16 psql -U user -d backend-app < backup.sql

# Drop and recreate database (CAREFUL!)
docker exec backend-php83 php bin/console doctrine:database:drop --force
docker exec backend-php83 php bin/console doctrine:database:create
docker exec backend-php83 php bin/console doctrine:migrations:migrate --no-interaction
```

### Symfony Console Commands

```bash
# Cache operations
docker exec backend-php83 php bin/console cache:clear
docker exec backend-php83 php bin/console cache:warmup

# Database operations
docker exec backend-php83 php bin/console doctrine:database:create
docker exec backend-php83 php bin/console doctrine:schema:update --dump-sql
docker exec backend-php83 php bin/console doctrine:migrations:status

# Debug commands
docker exec backend-php83 php bin/console debug:router
docker exec backend-php83 php bin/console debug:container
docker exec backend-php83 php bin/console debug:autowiring

# Messenger (queue) operations
docker exec backend-php83 php bin/console messenger:consume async -vv
docker exec backend-php83 php bin/console messenger:stats
```

### Code Quality Tools

#### PHP-CS-Fixer (Code Style)

PHP-CS-Fixer automatically fixes PHP code to follow PSR-12 and modern PHP 8.3 standards.

```bash
# Check code style (dry-run, no changes)
make cs-fixer-check
# OR
docker exec backend-php83 vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

# Fix code style automatically
make cs-fixer-fix
# OR
docker exec backend-php83 vendor/bin/php-cs-fixer fix --verbose
```

**Configuration:** `apps/backend/.php-cs-fixer.php`

**Key features:**
- PSR-12 compliance
- Modern PHP 8.3 features (strict types, readonly, enums)
- Array trailing commas
- Aligned binary operators
- Ordered class elements
- PHPDoc formatting

#### PHPStan (Static Analysis)

PHPStan performs static analysis to find bugs without running code.

```bash
# Run static analysis (level 5)
make phpstan
# OR
docker exec backend-php83 vendor/bin/phpstan analyse --memory-limit=1G

# Generate baseline (ignore existing errors)
make phpstan-baseline
# OR
docker exec backend-php83 vendor/bin/phpstan analyse --generate-baseline
```

**Configuration:** `apps/backend/phpstan.neon`

**Current level:** 5 (good starting point)

**Extensions enabled:**
- phpstan-symfony (Symfony-specific checks)
- phpstan-doctrine (Doctrine ORM checks)

#### Run All Quality Checks

```bash
# Check code style + static analysis
make quality-check

# Fix code style + run static analysis
make quality-fix
```

#### Git Pre-Commit Hooks

Automatically runs quality checks before each commit.

**Installation:**
```bash
# Install Git hooks (run once after cloning)
bash scripts/install-git-hooks.sh
```

**What it does:**
1. Detects changed PHP files in `apps/backend/`
2. Runs PHP-CS-Fixer check (dry-run)
3. Runs PHPStan analysis
4. Blocks commit if issues found

**Bypass hook** (not recommended):
```bash
git commit --no-verify
```

### Complete Project Rebuild

```bash
# WARNING: This will delete ALL data (database, cache, logs)

# 1. Stop and remove all containers
cd docker
docker-compose down -v  # -v removes volumes (database data!)

# 2. Remove all images (optional)
docker rmi $(docker images -q 'docker_*')

# 3. Rebuild containers from scratch
docker-compose build --no-cache

# 4. Start containers
docker-compose up -d

# 5. Reinstall backend dependencies
docker exec backend-php83 composer install

# 6. Recreate database
docker exec backend-php83 php bin/console doctrine:database:create
docker exec backend-php83 php bin/console doctrine:migrations:migrate --no-interaction

# 7. (Optional) Load fixtures
docker exec backend-php83 php bin/console doctrine:fixtures:load --no-interaction

# 8. Clear cache
docker exec backend-php83 php bin/console cache:clear
```

### Container Health Checks

```bash
# Check if all containers are running
docker-compose ps

# Check container resource usage
docker stats

# Check specific container health
docker inspect backend-php83 | grep -i health
docker inspect backend-psql16 | grep -i status

# Test backend API is responding
curl http://localhost:8089/api/health

# Test database connection
docker exec backend-php83 php bin/console doctrine:query:sql "SELECT 1"
```

---

## Testing

### Backend Tests

```bash
# Run all tests
docker exec backend-php83 php bin/phpunit

# Run specific test file
docker exec backend-php83 php bin/phpunit tests/Unit/Service/TaskServiceTest.php

# Run with coverage
docker exec backend-php83 php bin/phpunit --coverage-text
```

### Frontend Tests

```bash
cd frontend

# Run all tests
npm run test:run

# Run tests in watch mode
npm run test

# Run with coverage
npm run test:coverage
```

---

## Troubleshooting

### Container won't start

```bash
# Check logs
docker logs backend-php83

# Check if port is already in use
lsof -i :8089
lsof -i :15432

# Force remove and recreate
cd docker
docker-compose down
docker-compose up -d --force-recreate
```

### Database connection issues

```bash
# Check PostgreSQL is running
docker ps | grep psql16

# Test connection from PHP container
docker exec backend-php83 php -r "
try {
    \$pdo = new PDO('pgsql:host=psql16;port=5432;dbname=backend-app', 'user', 'password');
    echo 'Database connected!';
} catch (Exception \$e) {
    echo 'Database error: ' . \$e->getMessage();
}
"
```

### Performance issues

```bash
# Check resource usage
docker stats

# Clear all caches
docker exec backend-php83 php bin/console cache:clear

# Restart containers
cd docker
docker-compose restart
```

---

## Git Workflow

### Feature Branch

```bash
# Create feature branch
git checkout -b feature/my-feature

# Make changes, commit
git add .
git commit -m "Add my feature"

# Push to remote
git push -u origin feature/my-feature

# Create pull request on GitHub
```

---

## Related Documents

- **[Architecture](../backend/ARCHITECTURE.md)** - Code organization
- **[Testing](TESTING.md)** - Writing tests

---

*Last updated: 2025-01-05*
