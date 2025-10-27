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
