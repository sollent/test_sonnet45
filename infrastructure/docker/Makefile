.PHONY: help up down restart logs backend-console backend-migrate backend-cache frontend-dev frontend-build frontend-test

help:
	@echo "Available commands:"
	@echo "  make up              - Start all Docker services"
	@echo "  make down            - Stop all Docker services"
	@echo "  make restart         - Restart all services"
	@echo "  make logs            - Show logs"
	@echo "  make backend-console - Run Symfony console (use: make backend-console cmd='list')"
	@echo "  make backend-migrate - Run database migrations"
	@echo "  make backend-cache   - Clear Symfony cache"
	@echo "  make frontend-dev    - Start frontend dev server"
	@echo "  make frontend-build  - Build frontend for production"
	@echo "  make frontend-test   - Run frontend tests"

up:
	docker-compose up -d

down:
	docker-compose down

restart:
	docker-compose restart

logs:
	docker-compose logs -f

backend-console:
	docker exec backend-php83 php bin/console $(cmd)

backend-migrate:
	docker exec backend-php83 php bin/console doctrine:migrations:migrate --no-interaction

backend-cache:
	docker exec backend-php83 php bin/console cache:clear

frontend-dev:
	cd apps/frontend && npm run dev

frontend-build:
	cd apps/frontend && npm run build

frontend-test:
	cd apps/frontend && npm run test:run
