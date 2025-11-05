# 📚 Task Manager - Complete Documentation Index

> **For AI Assistants**: This documentation is specifically designed to provide complete project context. Read this INDEX first, then navigate to relevant sections based on your task.

---

## 🎯 Quick Start for AI

**New to this project?** Follow this reading order:

1. **[Project Overview](PROJECT_OVERVIEW.md)** - Understanding what we're building *(5 min read)*
2. **[Tech Stack](TECH_STACK.md)** - Technologies and versions *(3 min read)*
3. **[Coding Standards](CODING_STANDARDS.md)** - How we write code (CRITICAL) *(10 min read)*
4. **[Architecture](backend/ARCHITECTURE.md)** - System design patterns *(8 min read)*

**Already familiar?** Jump to:
- **[API Reference](backend/API_REFERENCE.md)** - All endpoints documented
- **[Troubleshooting](guides/TROUBLESHOOTING.md)** - Common issues & solutions
- **[Development Workflow](guides/DEVELOPMENT_WORKFLOW.md)** - Day-to-day development

---

## 📖 Documentation Structure

### 🌍 General

#### [`PROJECT_OVERVIEW.md`](PROJECT_OVERVIEW.md)
What is this application, its purpose, main features, and business logic

**Key Topics:**
- Application description
- Core features (tasks, subtasks, calendar, analytics)
- Business requirements
- User workflows

#### [`TECH_STACK.md`](TECH_STACK.md)
Complete technology stack with versions and justifications

**Key Topics:**
- Backend stack (Symfony 7.1, PHP 8.3, PostgreSQL)
- Frontend stack (Vue.js 3, TypeScript, PrimeVue)
- Infrastructure (Docker, Nginx)
- Third-party services (Google OAuth)

#### [`CODING_STANDARDS.md`](CODING_STANDARDS.md)
**⚠️ CRITICAL DOCUMENT** - Coding principles and best practices

**Key Topics:**
- SOLID principles (applied everywhere)
- GRASP principles (design patterns)
- GoF patterns (specific implementations)
- Backend conventions (Symfony)
- Frontend conventions (Vue + TypeScript)
- Code quality checklist

---

### 🔧 Backend (`project/backend/`)

#### [`backend/ARCHITECTURE.md`](backend/ARCHITECTURE.md)
Backend architecture, layers, and design patterns

**Key Topics:**
- Layered architecture (Controller → Service → Repository)
- Domain-Driven Design principles
- Dependency injection
- DTO pattern
- Event system
- Authentication & authorization


#### [`backend/API_REFERENCE.md`](backend/API_REFERENCE.md)
Complete API endpoint documentation

**Key Topics:**
- Authentication endpoints (JWT, Google OAuth)
- Task CRUD operations
- Tag management
- Analytics endpoints
- Request/Response examples
- Error responses
- Query parameters & filtering

#### [`backend/DATABASE.md`](backend/DATABASE.md)
Database schema, entities, and relationships

**Key Topics:**
- Entity relationship diagram
- Task entity (with unlimited nesting)
- User entity
- Tag entity
- JWT refresh token storage
- Migrations workflow

#### [`backend/AUTHENTICATION.md`](backend/AUTHENTICATION.md)
Authentication & authorization implementation

**Key Topics:**
- JWT token flow (access + refresh)
- Google OAuth2 integration
- Token refresh mechanism
- Security voters
- Role-based access control

---

### 🎨 Frontend (`project/frontend/`)

#### [`frontend/ARCHITECTURE.md`](frontend/ARCHITECTURE.md)
Frontend architecture and component organization

**Key Topics:**
- Composition API patterns
- Smart/Dumb components
- State management (Pinia stores)
- Composables architecture
- Service layer (API calls)
- TypeScript strict mode

#### [`frontend/COMPONENTS.md`](frontend/COMPONENTS.md)
Component library and usage patterns

**Key Topics:**
- Component hierarchy
- PrimeVue integration
- Reusable components
- Props & Events patterns
- Slot usage
- Styling conventions

#### [`frontend/STATE_MANAGEMENT.md`](frontend/STATE_MANAGEMENT.md)
Pinia stores and state patterns

**Key Topics:**
- Store organization (by domain)
- TaskStore (main state)
- AuthStore (authentication)
- Actions vs Getters
- Optimistic updates

#### [`frontend/API_INTEGRATION.md`](frontend/API_INTEGRATION.md)
How frontend communicates with backend

**Key Topics:**
- Axios configuration
- API service layer
- Request/Response interceptors
- Error handling
- Token management
- Retry logic

---

### 📘 Guides (`project/docs/guides/`)

#### [`guides/DEVELOPMENT_WORKFLOW.md`](guides/DEVELOPMENT_WORKFLOW.md)
Day-to-day development process

**Key Topics:**
- **Docker setup** - `docker/docker-compose.yml` (MAIN CONFIG)
- Running backend (Symfony via Docker)
- Running frontend (Vite: `cd frontend && npm run dev`)
- Complete project rebuild commands
- Database migrations & operations
- PostgreSQL operations
- Symfony console commands
- Container management (logs, restart, health checks)
- Testing workflow
- Git workflow & commits

#### [`guides/TROUBLESHOOTING.md`](guides/TROUBLESHOOTING.md)
Common issues and their solutions

**Key Topics:**
- CORS errors (solved)
- Date shifting (solved)
- UI blinking (solved)
- Memory exhaustion (solved)
- Docker issues
- Database connection issues

#### [`guides/TESTING.md`](guides/TESTING.md)
Testing strategy and running tests

**Key Topics:**
- Backend testing (PHPUnit)
- Frontend testing (Vitest)
- Test organization
- Writing new tests
- CI/CD integration

#### [`guides/DEPLOYMENT.md`](guides/DEPLOYMENT.md)
Production deployment guide

**Key Topics:**
- Environment configuration
- Docker production build
- Database setup
- SSL/HTTPS setup
- Monitoring & logs

---

## 🔑 Critical Knowledge Areas

### For Backend Development
**Must Read:**
1. [`CODING_STANDARDS.md`](CODING_STANDARDS.md) - SOLID/GRASP principles
2. [`backend/ARCHITECTURE.md`](backend/ARCHITECTURE.md) - Layered architecture

**Reference:**
- [`backend/API_REFERENCE.md`](backend/API_REFERENCE.md) - API contracts
- [`backend/DATABASE.md`](backend/DATABASE.md) - Schema design

### For Frontend Development
**Must Read:**
1. [`CODING_STANDARDS.md`](CODING_STANDARDS.md) - TypeScript/Vue conventions
2. [`frontend/ARCHITECTURE.md`](frontend/ARCHITECTURE.md) - Component patterns
3. [`frontend/STATE_MANAGEMENT.md`](frontend/STATE_MANAGEMENT.md) - Pinia stores

**Reference:**
- [`frontend/COMPONENTS.md`](frontend/COMPONENTS.md) - Component library
- [`frontend/API_INTEGRATION.md`](frontend/API_INTEGRATION.md) - API calls

### For Troubleshooting
**First Stop:**
- [`guides/TROUBLESHOOTING.md`](guides/TROUBLESHOOTING.md) - All known issues & fixes

**If Issue Persists:**
- [`guides/DEVELOPMENT_WORKFLOW.md`](guides/DEVELOPMENT_WORKFLOW.md) - Setup issues

---

## 📊 Project Statistics

```
Backend:
- Lines of Code: ~15,000
- Controllers: 4 (Auth, Task, Tag, Analytics)
- Services: 10+ (TaskService, AnalyticsService, etc.)
- Entities: 5 (User, Task, Tag, Media, RefreshToken)
- Tests: PHPUnit (Unit + Integration)

Frontend:
- Lines of Code: ~8,000
- Components: 25+ (views, cards, modals, forms)
- Composables: 8 (useTaskCompletion, useAuth, useTagSuggestions, etc.)
- Stores: 3 (TaskStore, AuthStore, LoaderStore)
- Tests: 115 (Vitest - 100% passing)
```

---

## 🎓 Learning Path for New AI Assistants

### Phase 1: Understanding (30 minutes)
1. Read `PROJECT_OVERVIEW.md` - What are we building?
2. Read `TECH_STACK.md` - What technologies?
3. Skim `CODING_STANDARDS.md` - How do we write code?

### Phase 2: Backend Deep Dive (45 minutes)
1. Study `backend/ARCHITECTURE.md` - How is backend structured?
2. Reference `backend/API_REFERENCE.md` - Know all endpoints
3. Read `backend/DATABASE.md` - Database schema and relationships

### Phase 3: Frontend Deep Dive (45 minutes)
1. Study `frontend/ARCHITECTURE.md` - How is frontend structured?
2. Read `frontend/STATE_MANAGEMENT.md` - How state works
3. Read `frontend/API_INTEGRATION.md` - How frontend talks to backend

### Phase 4: Practical Knowledge (30 minutes)
1. Read `guides/DEVELOPMENT_WORKFLOW.md` - How to develop
2. Bookmark `guides/TROUBLESHOOTING.md` - For when things break

**Total Time Investment:** ~2.5 hours for complete context

---

## 🚀 Quick Reference Commands

### Backend (Docker)

**IMPORTANT**: Docker configuration is in `docker/docker-compose.yml`

```bash
# Start services (from docker/ directory or use -f flag)
cd docker && docker-compose up -d
# OR from anywhere:
docker-compose -f docker/docker-compose.yml up -d

# Stop services
cd docker && docker-compose down

# Rebuild project completely
cd docker
docker-compose down -v  # Removes volumes (database!)
docker-compose build --no-cache
docker-compose up -d
docker exec backend-php83 composer install
docker exec backend-php83 php bin/console doctrine:database:create
docker exec backend-php83 php bin/console doctrine:migrations:migrate --no-interaction

# Run Symfony commands
docker exec backend-php83 php bin/console <command>

# Database migrations
docker exec backend-php83 php bin/console make:migration
docker exec backend-php83 php bin/console doctrine:migrations:migrate

# Clear cache
docker exec backend-php83 php bin/console cache:clear

# PostgreSQL operations
docker exec -it backend-psql16 psql -U user -d backend-app
docker exec backend-psql16 psql -U user -d backend-app -c "SELECT COUNT(*) FROM tasks;"

# Container logs
docker logs -f backend-php83
docker logs -f backend-nginx

# Container health
docker ps  # List running containers
docker stats  # Resource usage
```

### Frontend

**Location**: `frontend/` directory

```bash
# Navigate to frontend
cd frontend

# Install dependencies
npm install

# Development server (starts on http://localhost:3000)
npm run dev

# Type check
npm run type-check

# Build for production
npm run build

# Run tests
npm run test:run
```

---

## 📝 Documentation Conventions

### File Naming
- All documentation files: `UPPERCASE_WITH_UNDERSCORES.md`
- Backend-specific: `backend/FILE_NAME.md`
- Frontend-specific: `frontend/FILE_NAME.md`
- Guides: `guides/FILE_NAME.md`

### Document Structure
Every document follows this pattern:
1. **Title** with emoji
2. **Quick Summary** (TL;DR)
3. **Table of Contents** (for long docs)
4. **Main Content** with clear headings
5. **Examples** (code snippets)
6. **Related Documents** (links)

### Code Examples
- Always show both ❌ BAD and ✅ GOOD examples
- Include file paths for context
- Add comments explaining WHY

---

## 🔗 External Resources

### Official Documentation
- **Symfony**: https://symfony.com/doc/current/index.html
- **Vue.js 3**: https://vuejs.org/guide/introduction.html
- **TypeScript**: https://www.typescriptlang.org/docs/
- **PrimeVue**: https://primevue.org/
- **Pinia**: https://pinia.vuejs.org/

### Books Referenced
- **Clean Architecture** - Robert C. Martin
- **Clean Code** - Robert C. Martin
- **Code Complete** - Steve McConnell
- **Design Patterns** - Gang of Four

---

## ⚠️ Important Notes for AI

### Always Remember
1. **SOLID principles are non-negotiable** - Every class follows them
2. **No business logic in controllers** - Controllers are thin coordinators
3. **TypeScript strict mode** - No `any` types allowed
4. **Optimistic UI updates** - No full list reloads

### Before Making Changes
- [ ] Have you read the relevant docs?
- [ ] Do you understand the SOLID principles applied?
- [ ] Have you checked `TROUBLESHOOTING.md` for similar issues?
- [ ] Are you maintaining the existing code patterns?

### When Stuck
1. Check `guides/TROUBLESHOOTING.md` first
2. Re-read the relevant architecture doc
3. Review similar existing code
4. Ask for clarification if truly ambiguous

---

## 📅 Last Updated

**Version:** 1.0.0
**Date:** 2025-01-05
**Maintainer:** Claude Code AI
**Project Phase:** Production-Ready

---

## 🎯 Next Steps

After reading this INDEX:

**For Understanding Project:**
→ Start with [`PROJECT_OVERVIEW.md`](PROJECT_OVERVIEW.md)

**For Writing Backend Code:**
→ Read [`CODING_STANDARDS.md`](CODING_STANDARDS.md) then [`backend/ARCHITECTURE.md`](backend/ARCHITECTURE.md)

**For Writing Frontend Code:**
→ Read [`CODING_STANDARDS.md`](CODING_STANDARDS.md) then [`frontend/ARCHITECTURE.md`](frontend/ARCHITECTURE.md)

**For Fixing Issues:**
→ Jump to [`guides/TROUBLESHOOTING.md`](guides/TROUBLESHOOTING.md)

---

*This documentation is living and evolves with the project. When you make architectural decisions, update the relevant docs.*
