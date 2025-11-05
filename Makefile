.DEFAULT_GOAL := help

help: ## Show this help message
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

init: down build up ## Initialize environment

build: ## Build services.
	docker compose -f docker/docker-compose.yml build

up: ## Create and start services.
	docker compose -f docker/docker-compose.yml up -d

stop: ## Stop services.
	docker compose -f docker/docker-compose.yml stop

down: ## Stop and remove containers
	docker compose -f docker/docker-compose.yml down

remove:
	docker compose -f docker/docker-compose.yml down --rmi all --remove-orphans

logs: ## Display logs.
	docker compose -f docker/docker-compose.yml logs --tail=100 -f

console: ## Login in console.
	docker exec -it backend-php83 bash

psql:
	docker exec -it backend-container bash

## Frontend commands
kill-frontend: ## Kill frontend dev server on port 3000
	@echo "Killing process on port 3000..."
	@lsof -ti:3000 | xargs kill -9 2>/dev/null && echo "✓ Process killed" || echo "✗ No process found on port 3000"

kill-port: ## Kill process on specific port (usage: make kill-port PORT=3000)
	@if [ -z "$(PORT)" ]; then \
		echo "Error: PORT parameter required. Usage: make kill-port PORT=3000"; \
		exit 1; \
	fi
	@echo "Killing process on port $(PORT)..."
	@lsof -ti:$(PORT) | xargs kill -9 2>/dev/null && echo "✓ Process on port $(PORT) killed" || echo "✗ No process found on port $(PORT)"

frontend-dev: ## Start frontend dev server
	cd frontend && npm run dev

frontend-install: ## Install frontend dependencies
	cd frontend && npm install

frontend-build: ## Build frontend for production
	cd frontend && npm run build
