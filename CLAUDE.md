# 🤖 Claude Code Quick Reference

> **For Claude Code AI Assistant**: This file provides instant context about the project and navigation to comprehensive documentation.

---

## 📍 Start Here

**Complete documentation map**: [`docs/INDEX.md`](docs/INDEX.md)

👆 **Always start here** - Contains full navigation, learning path, and quick reference commands.

---

## ⚡ Essential Context

### What is this project?

**Task Management System** - Full-stack application with advanced features:
- **Backend**: Symfony 6.4 + PHP 8.3 + PostgreSQL 16 + Redis 7
- **Frontend**: Vue.js 3.4 + TypeScript 5.4 + Pinia + PrimeVue
- **Infrastructure**: Docker (all backend services)
- **Cache**: Hybrid UPDATE/INVALIDATE strategy (200-700x performance)

### Key Features
- ✅ Tasks with subtasks (unlimited nesting)
- ✅ Tags, priorities, statuses, due dates
- ✅ Advanced analytics (9 cached endpoints)
- ✅ Calendar integration
- ✅ JWT + Google OAuth authentication
- ✅ Redis caching with UPDATE strategy
- ✅ i18n support (EN/RU/UK)

---

## 🗺️ Documentation Map

### 🔥 Must Read First (For Code Changes)

1. **[`docs/CODING_STANDARDS.md`](docs/CODING_STANDARDS.md)** ⚠️ **CRITICAL**
   - SOLID, GRASP, GoF design patterns
   - PHP 8.3 modern standards (readonly, enums, match)
   - TypeScript strict mode rules
   - ✅ GOOD / ❌ BAD code examples

2. **[`docs/backend/CACHE_SYSTEM.md`](docs/backend/CACHE_SYSTEM.md)** ⚠️ **CRITICAL**
   - Hybrid UPDATE/INVALIDATE strategy
   - When to use each approach
   - Memory optimization (includeSubtasks: false!)
   - Redis commands & debugging

### 📚 Architecture & Setup

| Topic | Document | Key Info |
|-------|----------|----------|
| **Project Overview** | [`docs/PROJECT_OVERVIEW.md`](docs/PROJECT_OVERVIEW.md) | Features, workflows, capabilities |
| **Tech Stack** | [`docs/TECH_STACK.md`](docs/TECH_STACK.md) | All technologies with versions |
| **Backend Architecture** | [`docs/backend/ARCHITECTURE.md`](docs/backend/ARCHITECTURE.md) | Layered architecture, SOLID examples |
| **Database Schema** | [`docs/backend/DATABASE.md`](docs/backend/DATABASE.md) | Entities, relationships, migrations |
| **API Reference** | [`docs/backend/API_REFERENCE.md`](docs/backend/API_REFERENCE.md) | All 37 endpoints with examples |
| **Authentication** | [`docs/backend/AUTHENTICATION.md`](docs/backend/AUTHENTICATION.md) | JWT + Google OAuth flows |
| **Frontend Architecture** | [`docs/frontend/ARCHITECTURE.md`](docs/frontend/ARCHITECTURE.md) | Vue Composition API, Smart/Dumb components |
| **State Management** | [`docs/frontend/STATE_MANAGEMENT.md`](docs/frontend/STATE_MANAGEMENT.md) | Pinia stores (task, auth, tag, analytics) |
| **API Integration** | [`docs/frontend/API_INTEGRATION.md`](docs/frontend/API_INTEGRATION.md) | Axios, interceptors, error handling |

### 🛠️ Development Guides

| Topic | Document | Key Info |
|-------|----------|----------|
| **Daily Workflow** | [`docs/guides/DEVELOPMENT_WORKFLOW.md`](docs/guides/DEVELOPMENT_WORKFLOW.md) | Docker commands, migrations, Redis, PostgreSQL |
| **Testing** | [`docs/guides/TESTING.md`](docs/guides/TESTING.md) | PHPUnit, Vitest, test organization |
| **Troubleshooting** | [`docs/guides/TROUBLESHOOTING.md`](docs/guides/TROUBLESHOOTING.md) | All solved issues & solutions |

---

## 🚀 Quick Commands

### Docker (Backend)

```bash
# Start all services (from docker/ directory)
cd docker && docker-compose up -d

# Stop services
cd docker && docker-compose down

# View logs
docker logs -f backend-php83

# Symfony commands
docker exec backend-php83 php bin/console <command>

# Migrations
docker exec backend-php83 php bin/console doctrine:migrations:migrate

# Redis CLI
docker exec -it backend-redis redis-cli
```

**IMPORTANT**: Docker config is at `docker/docker-compose.yml`

### Frontend

```bash
# Start dev server
cd frontend && npm run dev

# Build for production
npm run build

# Run tests
npm run test:run
```

---

## 🔍 Finding Information

### When you need to know...

**"How to write code?"**
→ Read [`docs/CODING_STANDARDS.md`](docs/CODING_STANDARDS.md)

**"How does caching work?"**
→ Read [`docs/backend/CACHE_SYSTEM.md`](docs/backend/CACHE_SYSTEM.md)

**"What endpoints exist?"**
→ Read [`docs/backend/API_REFERENCE.md`](docs/backend/API_REFERENCE.md)

**"How to run the project?"**
→ Read [`docs/guides/DEVELOPMENT_WORKFLOW.md`](docs/guides/DEVELOPMENT_WORKFLOW.md)

**"How to fix [specific error]?"**
→ Read [`docs/guides/TROUBLESHOOTING.md`](docs/guides/TROUBLESHOOTING.md)

**"What is the database structure?"**
→ Read [`docs/backend/DATABASE.md`](docs/backend/DATABASE.md)

**"How to test?"**
→ Read [`docs/guides/TESTING.md`](docs/guides/TESTING.md)

**"Need complete context?"**
→ Start with [`docs/INDEX.md`](docs/INDEX.md) (2.5 hours reading path)

---

## ⚠️ Critical Rules (Always Follow!)

### Code Standards
✅ **DO**: Use type hints everywhere (PHP & TypeScript)
✅ **DO**: Follow SOLID principles (see CODING_STANDARDS.md)
✅ **DO**: Use readonly properties (PHP 8.2+)
✅ **DO**: Use enums instead of constants
✅ **DO**: Use match expressions instead of switch
❌ **DON'T**: Use `any` type in TypeScript
❌ **DON'T**: Write fat controllers (use services!)
❌ **DON'T**: Mix business logic with HTTP layer

### Cache Strategy
✅ **DO**: Use UPDATE strategy for task lists
✅ **DO**: Use INVALIDATE strategy for analytics
✅ **DO**: Set `includeSubtasks: false` for bulk cache operations
❌ **DON'T**: Include subtasks in list caches (memory exhaustion!)
❌ **DON'T**: Use Symfony Serializer for cache (use json_encode)

### Docker
✅ **DO**: Use `docker/docker-compose.yml` (main config)
✅ **DO**: Run backend commands via `docker exec backend-php83`
✅ **DO**: Check logs: `docker logs -f backend-php83`
❌ **DON'T**: Run PHP commands directly on host

---

## 📊 Project Structure

```
test_sonnet45/
├── CLAUDE.md                       # ← You are here!
├── docs/                           # ← Complete documentation
│   ├── INDEX.md                   # ← Start here for full navigation
│   ├── CODING_STANDARDS.md        # ⚠️ CRITICAL
│   ├── PROJECT_OVERVIEW.md
│   ├── TECH_STACK.md
│   ├── backend/
│   │   ├── CACHE_SYSTEM.md        # ⚠️ CRITICAL
│   │   ├── API_REFERENCE.md
│   │   ├── ARCHITECTURE.md
│   │   ├── DATABASE.md
│   │   └── AUTHENTICATION.md
│   ├── frontend/
│   │   ├── ARCHITECTURE.md
│   │   ├── STATE_MANAGEMENT.md
│   │   └── API_INTEGRATION.md
│   └── guides/
│       ├── DEVELOPMENT_WORKFLOW.md
│       ├── TESTING.md
│       └── TROUBLESHOOTING.md
├── docker/
│   └── docker-compose.yml          # Main Docker config
├── backend/                        # Symfony 6.4
│   ├── src/
│   │   ├── Controller/
│   │   ├── Service/
│   │   ├── Repository/
│   │   ├── Entity/
│   │   └── Dto/
│   └── config/
└── frontend/                       # Vue.js 3.4
    ├── src/
    │   ├── components/
    │   ├── views/
    │   ├── stores/
    │   └── services/
    └── package.json
```

---

## 🎯 Workflow for Claude Code

### When starting a new task:

1. **Read this file** (CLAUDE.md) for quick context
2. **Navigate to relevant docs** using links above
3. **Follow coding standards** (CODING_STANDARDS.md)
4. **Check cache strategy** if working with data (CACHE_SYSTEM.md)
5. **Write code** following patterns from documentation
6. **Test thoroughly** (TESTING.md)

### Before making changes:

- ✅ Understand the architecture (ARCHITECTURE.md)
- ✅ Check existing patterns in codebase
- ✅ Follow SOLID principles
- ✅ Update tests if needed

### If stuck or encountering errors:

1. Check [`docs/guides/TROUBLESHOOTING.md`](docs/guides/TROUBLESHOOTING.md)
2. Review related documentation section
3. Check Docker logs: `docker logs -f backend-php83`
4. Ask for clarification with specific context

---

## 📞 Access Points

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8089/api
- **PostgreSQL**: localhost:15432 (user/password/backend-app)
- **Redis**: localhost:16379
- **RabbitMQ**: http://localhost:15672 (user/password)

---

## 💡 Pro Tips

1. **Always read CODING_STANDARDS.md** before writing code
2. **Use UPDATE strategy** for frequently accessed data (tasks)
3. **Use INVALIDATE strategy** for complex calculations (analytics)
4. **Never include subtasks** in bulk cache operations
5. **All Docker commands** run from `docker/` directory or use `-f docker/docker-compose.yml`
6. **Redis cache keys** are deterministic: `app:prod:namespace:param1_value1:param2_value2`
7. **Frontend state** managed by Pinia stores (no Vuex!)
8. **Backend layers**: Controller → Service → Repository → Entity
9. **TypeScript strict mode** - no `any` types allowed!
10. **Complete rebuild**: See DEVELOPMENT_WORKFLOW.md "Complete Project Rebuild"

---

## 📚 Learning Path (New AI Assistant)

If you're a new Claude Code instance or need complete context:

**Phase 1 - Essential (30 min)**
1. This file (CLAUDE.md) - Quick reference
2. docs/INDEX.md - Navigation map
3. docs/PROJECT_OVERVIEW.md - What we're building

**Phase 2 - Code Standards (45 min)**
4. docs/CODING_STANDARDS.md ⚠️ **MUST READ**

**Phase 3 - Core Architecture (45 min)**
5. docs/backend/CACHE_SYSTEM.md ⚠️ **CRITICAL**
6. docs/backend/ARCHITECTURE.md
7. docs/frontend/ARCHITECTURE.md

**Phase 4 - Development (30 min)**
8. docs/guides/DEVELOPMENT_WORKFLOW.md
9. docs/guides/TROUBLESHOOTING.md

**Total**: ~2.5 hours for complete context

---

**Last Updated**: 2025-01-05

**Documentation Version**: 1.0

**For questions or clarifications**: Refer to [`docs/INDEX.md`](docs/INDEX.md) for detailed navigation
