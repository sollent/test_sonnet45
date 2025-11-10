.DEFAULT_GOAL := help

help: ## Show this help message
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

init: down build up ## Initialize environment

build: ## Build services.
	docker compose build

up: ## Create and start services.
	docker compose up -d

stop: ## Stop services.
	docker compose stop

down: ## Stop and remove containers
	docker compose down

remove:
	docker compose down --rmi all --remove-orphans

logs: ## Display logs.
	docker compose logs --tail=100 -f

console: ## Login in console.
	docker exec -it backend-php83 bash

psql:
	docker exec -it backend-psql16 bash

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
	cd apps/frontend && npm run dev

frontend-install: ## Install frontend dependencies
	cd apps/frontend && npm install

frontend-build: ## Build frontend for production
	cd apps/frontend && npm run build
