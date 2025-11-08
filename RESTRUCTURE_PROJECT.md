# 🏗️ Project Restructuring: Flat → Monorepo

> **For AI (Sonnet 4.5)**: This document provides step-by-step instructions for restructuring the project from a flat directory structure to a monorepo architecture with clear separation between application code and infrastructure.

---

## 📍 Context & Rationale

### Current Situation

The project currently has a **flat structure** where backend, frontend, and docker infrastructure are all at the root level:

```
test_sonnet45/
├── backend/              # Symfony app
├── frontend/             # Vue.js app
├── docker/               # Docker configs
├── docs/                 # Documentation
├── .claude/
├── CLAUDE.md
├── Makefile
└── .gitignore
```

### Problem

We're adding **Voice AI Assistant** functionality, which requires:
- Ollama (LLM service)
- Whisper.cpp (Speech-to-Text)
- Additional Centrifugo configuration
- Separate Docker compose files

The documentation in `docs/ai/01_INFRASTRUCTURE/01_SETUP.md` suggested creating separate folders (`task-manager/` and `voice-ai-services/`), but we need **everything in one Git repository** with better organization.

### Solution

Restructure to **monorepo architecture** with clear separation:
- **apps/** - All application code (backend, frontend)
- **infrastructure/** - All infrastructure (Docker, AI services)
- **scripts/** - Common utility scripts
- Keep root clean for meta files (.claude, CLAUDE.md, Makefile, etc.)

---

## 🎯 Target Structure

```
test_sonnet45/
├── apps/
│   ├── backend/                    # Symfony 7.1 + PHP 8.3
│   │   ├── src/
│   │   ├── config/
│   │   ├── migrations/
│   │   ├── tests/
│   │   ├── composer.json
│   │   └── ... (all existing backend files)
│   │
│   └── frontend/                   # Vue.js 3 + TypeScript
│       ├── src/
│       ├── public/
│       ├── package.json
│       ├── vite.config.ts
│       └── ... (all existing frontend files)
│
├── infrastructure/
│   ├── docker/                     # Docker configurations
│   │   ├── docker-compose.app.yml       # Backend + Frontend + PostgreSQL + RabbitMQ
│   │   ├── docker-compose.ai.yml        # Ollama + Whisper + Centrifugo (AI services)
│   │   ├── docker-compose.dev.yml       # Development overrides
│   │   ├── nginx/
│   │   ├── php/
│   │   └── postgres/
│   │
│   └── ai-services/                # AI infrastructure (PLACEHOLDERS ONLY for now)
│       ├── configs/
│       │   ├── ollama/
│       │   │   └── .gitkeep
│       │   ├── whisper/
│       │   │   └── .gitkeep
│       │   ├── centrifugo/
│       │   │   └── .gitkeep
│       │   └── nginx/
│       │       └── .gitkeep
│       └── scripts/
│           └── .gitkeep
│
├── scripts/                        # Common scripts
│   ├── setup-dev.sh                # Development setup
│   ├── reset-db.sh                 # Database reset
│   └── health-check.sh             # Services health check
│
├── docs/                           # Documentation (unchanged)
│   ├── INDEX.md
│   ├── CODING_STANDARDS.md
│   ├── ai/                         # Voice AI docs
│   ├── backend/
│   ├── frontend/
│   └── guides/
│
├── .claude/                        # Claude Code config (unchanged)
├── CLAUDE.md                       # Quick reference (NEEDS UPDATE)
├── Makefile                        # Root makefile (NEEDS UPDATE)
├── docker-compose.yml              # Main compose file (NEEDS UPDATE)
├── .gitignore                      # (unchanged)
├── README.md                       # (NEEDS UPDATE)
├── VOICE_AI_IMPLEMENTATION_PLAN.md # Voice AI plan (unchanged)
└── RESTRUCTURE_PROJECT.md          # This file
```

---

## ⚠️ IMPORTANT: Safety & Considerations

### ✅ What Will Be Preserved

1. **Git History** - Using `git mv` preserves full commit history
2. **Hidden Files** - All `.env`, `.gitignore`, `.dockerignore` files will move
3. **Symlinks** - If any exist, they'll be preserved
4. **File Permissions** - Executable scripts remain executable

### ⚠️ What Needs Updates

These files reference paths that will change:

**Backend:**
- `apps/backend/.env` → Update `DATABASE_URL` if needed
- `apps/backend/config/packages/centrifugo.yaml` → Paths unchanged (internal)

**Frontend:**
- `apps/frontend/.env` → Update `VITE_API_URL` (should be relative, no change)
- `apps/frontend/vite.config.ts` → Update `root` if needed

**Docker:**
- `infrastructure/docker/docker-compose.app.yml` → Update all `build: context` paths
- `infrastructure/docker/nginx/default.conf` → Update `root` paths to `/var/www/html/apps/backend/public`
- `infrastructure/docker/php/Dockerfile` → Update `WORKDIR` to `/var/www/html/apps/backend`

**Root Files:**
- `docker-compose.yml` → Rewrite to include sub-compose files
- `Makefile` → Update all paths (`cd backend` → `cd apps/backend`)
- `CLAUDE.md` → Update quick reference paths

**Documentation:**
- `docs/guides/DEVELOPMENT_WORKFLOW.md` → Update Docker commands
- `CLAUDE.md` → Update paths in commands

### 🚨 Potential Issues

1. **Docker Volume Mounts** - Need to update paths in compose files
2. **IDE Configurations** - PHPStorm/VSCode may need project reopen
3. **Absolute Paths in Scripts** - Any hardcoded paths need updates
4. **CI/CD** - If exists, update paths (none currently)

---

## 📋 Step-by-Step Implementation Plan

### Stage 0: Preparation (CRITICAL!)

**IMPORTANT**: Before starting, create a backup branch!

```bash
# 1. Ensure working directory is clean
git status

# 2. Commit any pending changes
git add .
git commit -m "chore: save work before restructuring"

# 3. Create backup branch
git checkout -b backup/before-restructure

# 4. Return to main branch
git checkout feature/optimization-backend-global  # or your current branch

# 5. Create new branch for restructuring
git checkout -b refactor/monorepo-structure
```

---

### Stage 1: Create New Directory Structure

```bash
# Navigate to project root
cd /Users/pavellaikov/Desktop/Projects/ai_sandbox/CURSOR/ULTRA/test_sonnet45

# Create new directories
mkdir -p apps
mkdir -p infrastructure/docker
mkdir -p infrastructure/ai-services/configs/{ollama,whisper,centrifugo,nginx}
mkdir -p infrastructure/ai-services/scripts
mkdir -p scripts

# Create .gitkeep files for empty AI directories (placeholders)
touch infrastructure/ai-services/configs/ollama/.gitkeep
touch infrastructure/ai-services/configs/whisper/.gitkeep
touch infrastructure/ai-services/configs/centrifugo/.gitkeep
touch infrastructure/ai-services/configs/nginx/.gitkeep
touch infrastructure/ai-services/scripts/.gitkeep
```

---

### Stage 2: Move Applications

```bash
# Move backend (preserves Git history)
git mv backend apps/backend

# Move frontend (preserves Git history)
git mv frontend apps/frontend

# Verify
ls -la apps/
# Should see: backend/ and frontend/
```

---

### Stage 3: Move Infrastructure

```bash
# Move entire docker directory
git mv docker/* infrastructure/docker/

# Remove empty docker directory
rmdir docker

# Verify
ls -la infrastructure/docker/
# Should see: docker-compose.yml, nginx/, php/, postgres/, etc.
```

---

### Stage 4: Reorganize Docker Configurations

```bash
# Rename existing docker-compose.yml
cd infrastructure/docker
git mv docker-compose.yml docker-compose.app.yml

# Move back to root
cd ../..
```

**Create placeholder files for AI infrastructure:**

```bash
# Create docker-compose.ai.yml (placeholder)
cat > infrastructure/docker/docker-compose.ai.yml << 'EOF'
# AI Services Docker Compose
# This is a PLACEHOLDER - will be implemented during Voice AI development

version: '3.8'

services:
  # TODO: Add Ollama service (LLM)
  # TODO: Add Whisper.cpp service (STT)
  # TODO: Add Centrifugo service (WebSocket)

  # Placeholder to prevent errors
  ai-placeholder:
    image: alpine:latest
    command: echo "AI services not implemented yet"
EOF

# Create docker-compose.dev.yml (development overrides)
cat > infrastructure/docker/docker-compose.dev.yml << 'EOF'
# Development Environment Overrides

version: '3.8'

services:
  backend-nginx:
    environment:
      - NGINX_DEBUG=true

  backend-php83:
    environment:
      - APP_ENV=dev
      - APP_DEBUG=true
    volumes:
      # Enable hot reload for development
      - ../../apps/backend:/var/www/html/apps/backend:cached
EOF
```

---

### Stage 5: Create Main Docker Compose (Root)

Create new `docker-compose.yml` in project root:

```yaml
# Main Docker Compose - Includes all sub-compose files
# Location: /test_sonnet45/docker-compose.yml

version: '3.8'

# Include all infrastructure compose files
include:
  - path: ./infrastructure/docker/docker-compose.app.yml
    env_file: ./apps/backend/.env

  # AI services (placeholder for now)
  - path: ./infrastructure/docker/docker-compose.ai.yml

  # Development overrides (optional)
  - path: ./infrastructure/docker/docker-compose.dev.yml

# No services defined here - all in included files
```

---

### Stage 6: Update Docker Configuration Files

#### Update `infrastructure/docker/docker-compose.app.yml`

**IMPORTANT**: All `build: context` and volume mount paths need updates!

Find and replace in `infrastructure/docker/docker-compose.app.yml`:

```yaml
# BEFORE:
build:
  context: ../backend
  dockerfile: ../docker/php/Dockerfile

# AFTER:
build:
  context: ../../apps/backend
  dockerfile: ../infrastructure/docker/php/Dockerfile
```

**Full updated sections:**

```yaml
services:
  backend-nginx:
    build:
      context: .
      dockerfile: ./nginx/Dockerfile
    volumes:
      - ../../apps/backend/public:/var/www/html/apps/backend/public:ro
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    # ... rest unchanged

  backend-php83:
    build:
      context: ../../apps/backend
      dockerfile: ../../infrastructure/docker/php/Dockerfile
    working_dir: /var/www/html/apps/backend
    volumes:
      - ../../apps/backend:/var/www/html/apps/backend:rw
    # ... rest unchanged

  backend-psql16:
    # No changes needed - no volume mounts to backend/frontend
    # ... unchanged

  backend-rabbitmq:
    # No changes needed
    # ... unchanged

  centrifugo:
    # No changes needed - config path relative to compose file
    # ... unchanged
```

#### Update `infrastructure/docker/nginx/default.conf`

```nginx
server {
    listen 80;
    server_name localhost;

    # BEFORE:
    # root /var/www/html/backend/public;

    # AFTER:
    root /var/www/html/apps/backend/public;

    index index.php;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass backend-php83:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;

        # BEFORE:
        # fastcgi_param SCRIPT_FILENAME /var/www/html/backend/public$fastcgi_script_name;

        # AFTER:
        fastcgi_param SCRIPT_FILENAME /var/www/html/apps/backend/public$fastcgi_script_name;

        fastcgi_param DOCUMENT_ROOT /var/www/html/apps/backend/public;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

#### Update `infrastructure/docker/php/Dockerfile`

```dockerfile
FROM php:8.3-fpm

# ... (all existing RUN commands unchanged)

# BEFORE:
# WORKDIR /var/www/html/backend

# AFTER:
WORKDIR /var/www/html/apps/backend

# ... rest unchanged
```

---

### Stage 7: Update Root Files

#### Update `Makefile`

```makefile
# BEFORE:
backend-console:
	docker exec backend-php83 php bin/console $(cmd)

# AFTER:
backend-console:
	cd apps/backend && docker exec backend-php83 php bin/console $(cmd)

# BEFORE:
frontend-dev:
	cd frontend && npm run dev

# AFTER:
frontend-dev:
	cd apps/frontend && npm run dev

# Update ALL similar commands
```

**Complete updated Makefile:**

```makefile
.PHONY: help up down restart logs backend-console frontend-dev

help:
	@echo "Available commands:"
	@echo "  make up              - Start all Docker services"
	@echo "  make down            - Stop all Docker services"
	@echo "  make restart         - Restart all services"
	@echo "  make logs            - Show logs"
	@echo "  make backend-console - Run Symfony console (use: make backend-console cmd='list')"
	@echo "  make frontend-dev    - Start frontend dev server"

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
```

#### Update `CLAUDE.md`

Find and replace all path references:

```markdown
# BEFORE:
├── backend/                        # Symfony 7.1
├── frontend/                       # Vue.js 3.4
├── docker/
│   └── docker-compose.yml          # Main Docker config

# AFTER:
├── apps/
│   ├── backend/                    # Symfony 7.1
│   └── frontend/                   # Vue.js 3.4
├── infrastructure/
│   └── docker/
│       ├── docker-compose.app.yml
│       └── docker-compose.ai.yml
├── docker-compose.yml              # Main compose (includes)
```

**Update Quick Commands section:**

```markdown
## 🚀 Quick Commands

### Docker (Backend)

**IMPORTANT**: Main Docker config is `docker-compose.yml` in root (includes infrastructure/docker/*.yml)

```bash
# Start services (from root)
docker-compose up -d

# Stop services
docker-compose down

# Run Symfony commands
docker exec backend-php83 php bin/console <command>

# Navigate to backend
cd apps/backend

# Navigate to frontend
cd apps/frontend
```

#### Update `docs/guides/DEVELOPMENT_WORKFLOW.md`

Update all Docker command examples:

```markdown
# BEFORE:
cd docker && docker-compose up -d

# AFTER:
docker-compose up -d  # Run from project root

# BEFORE:
cd frontend && npm run dev

# AFTER:
cd apps/frontend && npm run dev
```

---

### Stage 8: Create Utility Scripts

Create helpful scripts in `scripts/` directory:

**`scripts/setup-dev.sh`:**

```bash
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
```

**`scripts/reset-db.sh`:**

```bash
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
```

**`scripts/health-check.sh`:**

```bash
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
curl -s http://localhost:8089/api/health || echo "❌ Backend API not responding"

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
```

Make scripts executable:

```bash
chmod +x scripts/*.sh
```

---

### Stage 9: Update Documentation

#### Update `docs/ai/01_INFRASTRUCTURE/01_SETUP.md`

Find the directory structure section and update it:

```markdown
# BEFORE (showing separate folders):
task-manager/
voice-ai-services/

# AFTER (showing monorepo):
test_sonnet45/
├── apps/
│   ├── backend/
│   └── frontend/
├── infrastructure/
│   ├── docker/
│   └── ai-services/  # AI infrastructure here
```

**Add note at the beginning:**

```markdown
> **UPDATE (2025-01-09)**: The project structure has been reorganized into a monorepo.
> All paths in this document have been updated to reflect the new structure.
> - Application code: `apps/backend/` and `apps/frontend/`
> - Infrastructure: `infrastructure/docker/` and `infrastructure/ai-services/`
```

---

### Stage 10: Verification & Testing

After all changes, verify everything works:

```bash
# 1. Check Git status
git status
# Should show all moved files (git mv)

# 2. Test Docker build
docker-compose build

# 3. Start services
docker-compose up -d

# 4. Check container logs
docker-compose logs -f backend-php83

# 5. Check backend health
curl http://localhost:8089/api/health

# 6. Check database connection
docker exec backend-php83 php bin/console doctrine:query:sql "SELECT 1"

# 7. Test frontend
cd apps/frontend
npm install
npm run dev
# Visit http://localhost:3000

# 8. Run backend tests
docker exec backend-php83 php bin/phpunit

# 9. Run frontend tests
cd apps/frontend
npm run test:run
```

**Expected results:**
- ✅ All containers start successfully
- ✅ Backend API responds at http://localhost:8089
- ✅ Database connection works
- ✅ Frontend dev server starts
- ✅ All tests pass

---

### Stage 11: Commit Changes

```bash
# 1. Add all changes
git add .

# 2. Check what will be committed
git status

# 3. Commit with descriptive message
git commit -m "refactor: restructure project to monorepo architecture

- Move backend & frontend to apps/
- Move docker configs to infrastructure/docker/
- Create infrastructure/ai-services/ for future Voice AI
- Update all path references in Docker configs
- Update Makefile, CLAUDE.md, and documentation
- Add utility scripts (setup-dev, reset-db, health-check)
- Create main docker-compose.yml with includes

Benefits:
- Clear separation between app code and infrastructure
- Easier to add AI services later
- Better organization for monorepo
- Preserved full Git history with 'git mv'

BREAKING CHANGE: Docker commands now run from project root"

# 4. Verify commit
git log -1 --stat

# 5. (Optional) Push to remote
git push origin refactor/monorepo-structure
```

---

## ✅ Post-Migration Checklist

After completing all stages, verify:

### Functionality
- [ ] Docker containers start without errors
- [ ] Backend API responds at http://localhost:8089/api
- [ ] Database connection works
- [ ] Frontend dev server starts (http://localhost:3000)
- [ ] Can login with existing credentials
- [ ] Can create/edit/delete tasks
- [ ] Backend tests pass
- [ ] Frontend tests pass

### Git History
- [ ] Git log shows preserved history for moved files
- [ ] No file content changed (only locations)
- [ ] All hidden files (.env, .gitignore) preserved

### Documentation
- [ ] CLAUDE.md updated with new paths
- [ ] docs/guides/DEVELOPMENT_WORKFLOW.md updated
- [ ] docs/ai/01_INFRASTRUCTURE/01_SETUP.md updated
- [ ] README.md updated (if exists)

### Scripts & Config
- [ ] Makefile commands work
- [ ] Utility scripts (scripts/*.sh) work
- [ ] Docker compose includes work correctly
- [ ] All volume mounts point to correct paths

---

## 🚨 Rollback Plan (If Something Goes Wrong)

If anything breaks during migration:

```bash
# Option 1: Abort and return to backup branch
git reset --hard HEAD
git checkout backup/before-restructure

# Option 2: Restore specific files
git checkout HEAD -- path/to/file

# Option 3: Restore from backup branch
git checkout backup/before-restructure -- path/to/file
```

---

## 📊 Summary of Changes

### Moved
- `backend/` → `apps/backend/`
- `frontend/` → `apps/frontend/`
- `docker/` → `infrastructure/docker/`

### Created
- `apps/` - Application code directory
- `infrastructure/` - Infrastructure directory
- `infrastructure/ai-services/` - AI infrastructure (placeholders)
- `scripts/` - Utility scripts
- `docker-compose.yml` - Main compose with includes

### Updated
- `infrastructure/docker/docker-compose.app.yml` - Renamed from docker-compose.yml, updated paths
- `infrastructure/docker/nginx/default.conf` - Updated root paths
- `infrastructure/docker/php/Dockerfile` - Updated WORKDIR
- `Makefile` - Updated all paths
- `CLAUDE.md` - Updated quick reference
- `docs/guides/DEVELOPMENT_WORKFLOW.md` - Updated commands
- `docs/ai/01_INFRASTRUCTURE/01_SETUP.md` - Updated structure

### Added
- `infrastructure/docker/docker-compose.ai.yml` - Placeholder for AI services
- `infrastructure/docker/docker-compose.dev.yml` - Development overrides
- `scripts/setup-dev.sh` - Development setup script
- `scripts/reset-db.sh` - Database reset script
- `scripts/health-check.sh` - Service health check

---

## 🎯 What This Achieves

### Benefits

1. **Clear Separation**
   - Application code isolated in `apps/`
   - Infrastructure isolated in `infrastructure/`
   - No mixing of concerns

2. **Scalability**
   - Easy to add new apps (e.g., `apps/mobile-app/`)
   - Easy to add new services (e.g., `infrastructure/monitoring/`)
   - Prepared for Voice AI services

3. **Developer Experience**
   - Root directory is clean and readable
   - Docker commands run from root (consistent)
   - Easy to navigate with clear naming

4. **Git History Preserved**
   - All commit history intact
   - Can trace file origins
   - No data loss

### Future-Ready

When implementing Voice AI Assistant (Phase 2), you'll simply:
1. Add real configs to `infrastructure/ai-services/configs/`
2. Update `infrastructure/docker/docker-compose.ai.yml` with real services
3. No restructuring needed - everything is already organized

---

## 📞 Support

If you encounter issues during migration:

1. **Check Docker logs**: `docker-compose logs -f`
2. **Verify paths**: Ensure all path references updated
3. **Review this document**: Follow each stage carefully
4. **Rollback if needed**: Use backup branch created in Stage 0

---

## 🤖 For Sonnet 4.5

**You can execute this plan autonomously by:**

1. Reading this document fully
2. Following stages 0-11 in order
3. Using Git commands exactly as shown
4. Verifying at each checkpoint
5. Creating backup branch before starting
6. Committing changes only after full verification

**Estimated time**: 30-45 minutes

**Risk level**: Low (Git history preserved, backup branch created)

**Testing required**: Yes (Stage 10 verification)

---

**Last Updated**: 2025-01-09
**Version**: 1.0
**Status**: Ready for implementation
