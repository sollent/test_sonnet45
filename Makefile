.DEFAULT_GOAL := help

# Docker Compose wrapper для автоматической загрузки .env
DOCKER_COMPOSE = ./scripts/docker-compose-wrapper.sh

# Docker Compose файлы (модульная структура)
COMPOSE_BASE = docker-compose.yml
COMPOSE_DEV = infrastructure/docker/docker-compose.dev.yml
COMPOSE_FRONTEND_DEV = infrastructure/docker/docker-compose.frontend-dev.yml
COMPOSE_PROD = infrastructure/docker/docker-compose-prod.yml
COMPOSE_FRONTEND_PROD = infrastructure/docker/docker-compose.frontend-prod.yml

# Полные команды для разных окружений
COMPOSE_DEV_FULL = $(DOCKER_COMPOSE) -f $(COMPOSE_BASE) -f $(COMPOSE_DEV) -f $(COMPOSE_FRONTEND_DEV)
COMPOSE_PROD_FULL = $(DOCKER_COMPOSE) -f $(COMPOSE_BASE) -f $(COMPOSE_PROD) -f $(COMPOSE_FRONTEND_PROD)

help: ## Show this help message
	@echo "🚀 Available commands:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-25s\033[0m %s\n", $$1, $$2}'

## 🏗️  Development Environment Commands

init: down build up migrate ## 🎯 Initialize full DEV environment (backend + frontend)
	@echo ""
	@echo "✅ Development environment ready!"
	@echo "   Backend:  http://localhost:8089"
	@echo "   Frontend: http://localhost:3000"
	@echo ""
	@echo "📝 Useful commands:"
	@echo "   make logs          - View all logs"
	@echo "   make logs-backend  - View backend logs"
	@echo "   make logs-frontend - View frontend logs"
	@echo "   make console       - Enter backend container"
	@echo "   make down          - Stop all services"

build: ## Build all services (dev mode)
	@echo "🔨 Building services..."
	$(COMPOSE_DEV_FULL) build

up: ## Start all services (dev mode: backend + frontend)
	@echo "🚀 Starting development environment..."
	$(COMPOSE_DEV_FULL) up -d
	@echo "⏳ Waiting for services to be ready..."
	@sleep 5

stop: ## Stop all services
	@echo "⏸️  Stopping services..."
	$(COMPOSE_DEV_FULL) stop

down: ## Stop and remove all containers
	@echo "🛑 Stopping and removing containers..."
	$(COMPOSE_DEV_FULL) down

restart: down up ## Restart all services

remove: ## Remove containers, images, and volumes
	@echo "🗑️  Removing containers, images, and volumes..."
	$(COMPOSE_DEV_FULL) down --rmi all --volumes --remove-orphans

## 🔍 Logs Commands

logs: ## Display logs for all services
	$(COMPOSE_DEV_FULL) logs --tail=100 -f

logs-backend: ## Display backend (PHP-FPM + Nginx) logs
	$(COMPOSE_DEV_FULL) logs --tail=100 -f php83-fpm nginx

logs-frontend: ## Display frontend logs
	$(COMPOSE_DEV_FULL) logs --tail=100 -f frontend

logs-db: ## Display database logs
	$(COMPOSE_DEV_FULL) logs --tail=100 -f psql16

## 🐚 Shell Access Commands

console: ## Enter backend PHP container
	$(COMPOSE_DEV_FULL) exec php83-fpm bash

console-frontend: ## Enter frontend container
	$(COMPOSE_DEV_FULL) exec frontend sh

psql: ## Enter PostgreSQL container
	$(COMPOSE_DEV_FULL) exec psql16 bash

db-cli: ## Connect to PostgreSQL CLI
	$(COMPOSE_DEV_FULL) exec psql16 psql -U user -d backend-app

## 🗄️  Database Commands

migrate: ## Run database migrations
	@echo "🗄️  Running migrations..."
	$(COMPOSE_DEV_FULL) exec php83-fpm php bin/console doctrine:migrations:migrate --no-interaction

migrate-create: ## Create new migration
	$(COMPOSE_DEV_FULL) exec php83-fpm php bin/console make:migration

db-reset: ## Reset database (drop + create + migrate)
	@echo "⚠️  Resetting database..."
	$(COMPOSE_DEV_FULL) exec php83-fpm php bin/console doctrine:database:drop --force --if-exists
	$(COMPOSE_DEV_FULL) exec php83-fpm php bin/console doctrine:database:create
	$(COMPOSE_DEV_FULL) exec php83-fpm php bin/console doctrine:migrations:migrate --no-interaction

## 🎨 Frontend Commands (Local - без Docker)

frontend-install: ## Install frontend dependencies locally
	cd apps/frontend && npm install

frontend-dev-local: ## Start frontend dev server LOCALLY (без Docker)
	@echo "🎨 Starting frontend locally on http://localhost:3000"
	@echo "⚠️  Note: Backend должен быть запущен в Docker!"
	cd apps/frontend && npm run dev

frontend-build: ## Build frontend for production
	cd apps/frontend && npm run build

## 🔧 Utility Commands

kill-frontend: ## Kill local frontend process on port 3000
	@echo "Killing process on port 3000..."
	@lsof -ti:3000 | xargs kill -9 2>/dev/null && echo "✓ Process killed" || echo "✗ No process found on port 3000"

kill-port: ## Kill process on specific port (usage: make kill-port PORT=3000)
	@if [ -z "$(PORT)" ]; then \
		echo "Error: PORT parameter required. Usage: make kill-port PORT=3000"; \
		exit 1; \
	fi
	@echo "Killing process on port $(PORT)..."
	@lsof -ti:$(PORT) | xargs kill -9 2>/dev/null && echo "✓ Process on port $(PORT) killed" || echo "✗ No process found on port $(PORT)"

## 🏭 Production Environment Commands

prod-build: ## Build production images
	@echo "🔨 Building production images..."
	$(COMPOSE_PROD_FULL) build

prod-up: ## Start production environment
	@echo "🚀 Starting production environment..."
	@echo "⚠️  Make sure .env.docker.prod is configured!"
	$(COMPOSE_PROD_FULL) up -d
	@echo ""
	@echo "✅ Production environment started!"
	@echo "   Backend:  http://localhost:8089"
	@echo "   Frontend: http://localhost:8080"

prod-down: ## Stop production environment
	@echo "🛑 Stopping production environment..."
	$(COMPOSE_PROD_FULL) down

prod-logs: ## View production logs
	$(COMPOSE_PROD_FULL) logs --tail=100 -f

## ✅ Backend Quality Tools

cs-fixer-check: ## Check PHP code style (dry-run)
	@echo "🔍 Checking PHP code style..."
	$(COMPOSE_DEV_FULL) exec php83-fpm vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

cs-fixer-fix: ## Fix PHP code style automatically
	@echo "🔧 Fixing PHP code style..."
	$(COMPOSE_DEV_FULL) exec php83-fpm vendor/bin/php-cs-fixer fix --verbose

phpstan: ## Run PHPStan static analysis
	@echo "🔬 Running PHPStan analysis..."
	$(COMPOSE_DEV_FULL) exec php83-fpm vendor/bin/phpstan analyse --memory-limit=1G

phpstan-baseline: ## Generate PHPStan baseline
	$(COMPOSE_DEV_FULL) exec php83-fpm vendor/bin/phpstan analyse --generate-baseline

quality-check: ## Run all quality checks (cs-fixer + phpstan)
	@echo "🔍 Running all quality checks..."
	@echo ""
	@echo "📝 PHP-CS-Fixer check..."
	@$(COMPOSE_DEV_FULL) exec php83-fpm vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
	@echo ""
	@echo "🔬 PHPStan analysis..."
	@$(COMPOSE_DEV_FULL) exec php83-fpm vendor/bin/phpstan analyse --memory-limit=1G
	@echo ""
	@echo "✅ All quality checks completed!"

quality-fix: ## Fix code style and re-run checks
	@echo "🔧 Fixing code style..."
	@$(COMPOSE_DEV_FULL) exec php83-fpm vendor/bin/php-cs-fixer fix --verbose
	@echo ""
	@echo "🔬 Running PHPStan analysis..."
	@$(COMPOSE_DEV_FULL) exec php83-fpm vendor/bin/phpstan analyse --memory-limit=1G
	@echo ""
	@echo "✅ Code fixed and analyzed!"

## 🧪 Testing Commands

test: ## Run all backend tests
	@echo "🧪 Running all tests..."
	$(COMPOSE_DEV_FULL) exec php83-fpm sh -c "APP_ENV=test php bin/phpunit"

test-unit: ## Run unit tests only
	$(COMPOSE_DEV_FULL) exec php83-fpm sh -c "APP_ENV=test php bin/phpunit --testsuite unit"

test-integration: ## Run integration tests only
	$(COMPOSE_DEV_FULL) exec php83-fpm sh -c "APP_ENV=test php bin/phpunit --testsuite integration"

test-coverage: ## Generate test coverage report
	$(COMPOSE_DEV_FULL) exec php83-fpm sh -c "APP_ENV=test php bin/phpunit --coverage-html coverage"

## 📊 Status Commands

status: ## Show status of all services
	@echo "📊 Services status:"
	@echo ""
	$(COMPOSE_DEV_FULL) ps

ps: status ## Alias for status

## 🌳 Git Worktree Commands

worktree-create: ## Create new worktree (usage: make worktree-create BRANCH=feature/name NAME=feature-name)
	@if [ -z "$(BRANCH)" ] || [ -z "$(NAME)" ]; then \
		echo "❌ Error: BRANCH and NAME parameters required"; \
		echo ""; \
		echo "Usage:"; \
		echo "  make worktree-create BRANCH=feature/new-api NAME=feature-new-api"; \
		echo ""; \
		echo "Examples:"; \
		echo "  make worktree-create BRANCH=feature/caching NAME=feature-caching"; \
		echo "  make worktree-create BRANCH=bugfix/cors NAME=bugfix-cors"; \
		exit 1; \
	fi
	@./scripts/worktree/worktree-create.sh $(BRANCH) $(NAME)

worktree-list: ## Show all worktrees with Docker containers and ports
	@./scripts/worktree/worktree-list.sh

worktree-remove: ## Remove worktree (usage: make worktree-remove NAME=feature-name)
	@if [ -z "$(NAME)" ]; then \
		echo "❌ Error: NAME parameter required"; \
		echo ""; \
		echo "Usage:"; \
		echo "  make worktree-remove NAME=feature-new-api"; \
		echo ""; \
		echo "Available worktrees:"; \
		git worktree list; \
		exit 1; \
	fi
	@./scripts/worktree/worktree-remove.sh $(NAME)

worktree-stop-all: ## Stop Docker containers in all worktrees
	@./scripts/worktree/worktree-stop-all.sh

wt-create: worktree-create ## Alias for worktree-create
wt-list: worktree-list ## Alias for worktree-list
wt-ls: worktree-list ## Alias for worktree-list
wt-remove: worktree-remove ## Alias for worktree-remove
wt-rm: worktree-remove ## Alias for worktree-remove
wt-stop: worktree-stop-all ## Alias for worktree-stop-all
