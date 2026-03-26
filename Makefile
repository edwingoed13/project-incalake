.PHONY: help install dev build test clean docker-up docker-down docker-logs migrate seed

# Variables
BACKEND_DIR = backend
FRONTEND_DIR = frontend
ADMIN_DIR = admin

# Colors for terminal output
RED = \033[0;31m
GREEN = \033[0;32m
YELLOW = \033[1;33m
NC = \033[0m # No Color

# Default target
help:
	@echo "$(GREEN)Incalake Full Stack - Makefile Commands$(NC)"
	@echo ""
	@echo "$(YELLOW)Installation:$(NC)"
	@echo "  make install        - Install all dependencies (backend, frontend, admin)"
	@echo "  make install-backend - Install backend dependencies only"
	@echo "  make install-frontend - Install frontend dependencies only"
	@echo "  make install-admin  - Install admin dependencies only"
	@echo ""
	@echo "$(YELLOW)Development:$(NC)"
	@echo "  make dev           - Start all development servers"
	@echo "  make dev-backend   - Start backend server only"
	@echo "  make dev-frontend  - Start frontend server only"
	@echo "  make dev-admin     - Start admin server only"
	@echo ""
	@echo "$(YELLOW)Build:$(NC)"
	@echo "  make build         - Build frontend and admin for production"
	@echo "  make build-frontend - Build frontend only"
	@echo "  make build-admin   - Build admin only"
	@echo ""
	@echo "$(YELLOW)Database:$(NC)"
	@echo "  make migrate       - Run database migrations"
	@echo "  make migrate-fresh - Fresh migration with seeders"
	@echo "  make seed          - Run database seeders"
	@echo ""
	@echo "$(YELLOW)Docker:$(NC)"
	@echo "  make docker-up     - Start Docker containers"
	@echo "  make docker-down   - Stop Docker containers"
	@echo "  make docker-build  - Build Docker containers"
	@echo "  make docker-logs   - View Docker logs"
	@echo ""
	@echo "$(YELLOW)Testing:$(NC)"
	@echo "  make test          - Run all tests"
	@echo "  make test-backend  - Run backend tests"
	@echo "  make test-frontend - Run frontend tests"
	@echo ""
	@echo "$(YELLOW)Maintenance:$(NC)"
	@echo "  make clean         - Clean all build artifacts and caches"
	@echo "  make cache-clear   - Clear Laravel caches"
	@echo "  make optimize      - Optimize Laravel for production"

# Installation commands
install:
	@echo "$(GREEN)Installing all dependencies...$(NC)"
	npm install
	cd $(BACKEND_DIR) && composer install
	cd $(FRONTEND_DIR) && npm install
	cd $(ADMIN_DIR) && npm install
	@echo "$(GREEN)All dependencies installed successfully!$(NC)"

install-backend:
	@echo "$(GREEN)Installing backend dependencies...$(NC)"
	cd $(BACKEND_DIR) && composer install

install-frontend:
	@echo "$(GREEN)Installing frontend dependencies...$(NC)"
	cd $(FRONTEND_DIR) && npm install

install-admin:
	@echo "$(GREEN)Installing admin dependencies...$(NC)"
	cd $(ADMIN_DIR) && npm install

# Development commands
dev:
	@echo "$(GREEN)Starting all development servers...$(NC)"
	npm run dev

dev-backend:
	@echo "$(GREEN)Starting backend server...$(NC)"
	cd $(BACKEND_DIR) && php artisan serve --port=8001

dev-frontend:
	@echo "$(GREEN)Starting frontend server...$(NC)"
	cd $(FRONTEND_DIR) && npm run dev -- --port 3001

dev-admin:
	@echo "$(GREEN)Starting admin server...$(NC)"
	cd $(ADMIN_DIR) && npm run dev -- --port 54112

# Build commands
build:
	@echo "$(GREEN)Building for production...$(NC)"
	cd $(FRONTEND_DIR) && npm run build
	cd $(ADMIN_DIR) && npm run build
	@echo "$(GREEN)Build completed!$(NC)"

build-frontend:
	@echo "$(GREEN)Building frontend...$(NC)"
	cd $(FRONTEND_DIR) && npm run build

build-admin:
	@echo "$(GREEN)Building admin panel...$(NC)"
	cd $(ADMIN_DIR) && npm run build

# Database commands
migrate:
	@echo "$(GREEN)Running migrations...$(NC)"
	cd $(BACKEND_DIR) && php artisan migrate

migrate-fresh:
	@echo "$(YELLOW)Running fresh migration with seeds...$(NC)"
	cd $(BACKEND_DIR) && php artisan migrate:fresh --seed

seed:
	@echo "$(GREEN)Running seeders...$(NC)"
	cd $(BACKEND_DIR) && php artisan db:seed

# Docker commands
docker-up:
	@echo "$(GREEN)Starting Docker containers...$(NC)"
	docker-compose up -d

docker-down:
	@echo "$(YELLOW)Stopping Docker containers...$(NC)"
	docker-compose down

docker-build:
	@echo "$(GREEN)Building Docker containers...$(NC)"
	docker-compose build

docker-logs:
	docker-compose logs -f

# Testing commands
test:
	@echo "$(GREEN)Running all tests...$(NC)"
	cd $(BACKEND_DIR) && php artisan test
	cd $(FRONTEND_DIR) && npm run test || true
	cd $(ADMIN_DIR) && npm run test || true

test-backend:
	@echo "$(GREEN)Running backend tests...$(NC)"
	cd $(BACKEND_DIR) && php artisan test

test-frontend:
	@echo "$(GREEN)Running frontend tests...$(NC)"
	cd $(FRONTEND_DIR) && npm run test

test-admin:
	@echo "$(GREEN)Running admin tests...$(NC)"
	cd $(ADMIN_DIR) && npm run test

# Maintenance commands
clean:
	@echo "$(YELLOW)Cleaning build artifacts...$(NC)"
	cd $(BACKEND_DIR) && rm -rf vendor bootstrap/cache storage/logs/*.log
	cd $(FRONTEND_DIR) && rm -rf node_modules .nuxt .output dist
	cd $(ADMIN_DIR) && rm -rf node_modules .nuxt .output dist
	rm -rf node_modules
	@echo "$(GREEN)Clean completed!$(NC)"

cache-clear:
	@echo "$(GREEN)Clearing Laravel caches...$(NC)"
	cd $(BACKEND_DIR) && php artisan cache:clear
	cd $(BACKEND_DIR) && php artisan config:clear
	cd $(BACKEND_DIR) && php artisan route:clear
	cd $(BACKEND_DIR) && php artisan view:clear

optimize:
	@echo "$(GREEN)Optimizing Laravel for production...$(NC)"
	cd $(BACKEND_DIR) && composer install --optimize-autoloader --no-dev
	cd $(BACKEND_DIR) && php artisan config:cache
	cd $(BACKEND_DIR) && php artisan route:cache
	cd $(BACKEND_DIR) && php artisan view:cache
	@echo "$(GREEN)Optimization completed!$(NC)"

# Quick setup for new developers
setup: install migrate seed
	@echo "$(GREEN)Initial setup completed!$(NC)"
	@echo "Run 'make dev' to start all servers"