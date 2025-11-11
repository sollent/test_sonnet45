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
- **Backend**: Symfony 7.1 + PHP 8.3 + PostgreSQL 16
- **Frontend**: Vue.js 3.4 + TypeScript 5.4 + Pinia + PrimeVue
- **Infrastructure**: Docker (all backend services)

### Key Features
- ✅ Tasks with subtasks (unlimited nesting)
- ✅ Tags, priorities, statuses, due dates
- ✅ Advanced analytics
- ✅ Calendar integration
- ✅ JWT + Google OAuth authentication
- ✅ i18n support (EN/RU/UK)

---

## 🗺️ Documentation Map

### 🔥 Must Read First (For Code Changes)

1. **[`docs/CODING_STANDARDS.md`](docs/CODING_STANDARDS.md)** ⚠️ **CRITICAL**
   - SOLID, GRASP, GoF design patterns
   - PHP 8.3 modern standards (readonly, enums, match)
   - TypeScript strict mode rules
   - ✅ GOOD / ❌ BAD code examples

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
| **Daily Workflow** | [`docs/guides/DEVELOPMENT_WORKFLOW.md`](docs/guides/DEVELOPMENT_WORKFLOW.md) | Docker commands, migrations, PostgreSQL |
| **Code Quality** | [`docs/guides/CODE_QUALITY.md`](docs/guides/CODE_QUALITY.md) | PHP-CS-Fixer, PHPStan, Git hooks |
| **Testing** | [`docs/guides/testing/TESTING.md`](docs/guides/testing/TESTING.md) | PHPUnit, Vitest, test organization |
| **Troubleshooting** | [`docs/guides/TROUBLESHOOTING.md`](docs/guides/TROUBLESHOOTING.md) | All solved issues & solutions |

---

## 🚀 Quick Commands

### Docker (Backend)

```bash
# Start all services (from project root)
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker logs -f backend-php83

# Symfony commands
docker exec backend-php83 php bin/console <command>

# Migrations
docker exec backend-php83 php bin/console doctrine:migrations:migrate

# Code quality checks
make cs-fixer-fix              # Fix code style
make phpstan                   # Static analysis
make quality-check             # Run both checks
```

**IMPORTANT**: Main Docker config is `docker-compose.yml` in root (includes infrastructure/docker/*.yml)

### Frontend

```bash
# Start dev server
cd apps/frontend && npm run dev

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

**"What endpoints exist?"**
→ Read [`docs/backend/API_REFERENCE.md`](docs/backend/API_REFERENCE.md)

**"How to run the project?"**
→ Read [`docs/guides/DEVELOPMENT_WORKFLOW.md`](docs/guides/DEVELOPMENT_WORKFLOW.md)

**"How to fix [specific error]?"**
→ Read [`docs/guides/TROUBLESHOOTING.md`](docs/guides/TROUBLESHOOTING.md)

**"What is the database structure?"**
→ Read [`docs/backend/DATABASE.md`](docs/backend/DATABASE.md)

**"How to test?"**
→ Read [`docs/guides/testing/TESTING.md`](docs/guides/testing/TESTING.md)

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

### Docker
✅ **DO**: Use `docker-compose.yml` in root (main config, includes infrastructure/docker/*.yml)
✅ **DO**: Run backend commands via `docker exec backend-php83`
✅ **DO**: Check logs: `docker logs -f backend-php83`
❌ **DON'T**: Run PHP commands directly on host

---

## 📊 Project Structure

```
test_sonnet45/
├── CLAUDE.md                       # ← You are here!
├── docker-compose.yml              # Main Docker compose (includes)
├── Makefile                        # Common commands
├── docs/                           # ← Complete documentation
│   ├── INDEX.md                   # ← Start here for full navigation
│   ├── CODING_STANDARDS.md        # ⚠️ CRITICAL
│   ├── PROJECT_OVERVIEW.md
│   ├── TECH_STACK.md
│   ├── backend/
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
├── apps/
│   ├── backend/                    # Symfony 7.1
│   │   ├── src/
│   │   │   ├── Controller/
│   │   │   ├── Service/
│   │   │   ├── Repository/
│   │   │   ├── Entity/
│   │   │   └── Dto/
│   │   └── config/
│   └── frontend/                   # Vue.js 3.4
│       ├── src/
│       │   ├── components/
│       │   ├── views/
│       │   ├── stores/
│       │   └── services/
│       └── package.json
├── infrastructure/
│   ├── docker/
│   │   ├── docker-compose.app.yml
│   │   ├── docker-compose.ai.yml   # AI services (placeholder)
│   │   └── docker-compose.dev.yml
│   └── ai-services/                # AI infrastructure (placeholder)
└── scripts/                        # Utility scripts
```

---

## 🎯 Workflow for Claude Code

### When starting a new task:

1. **Read this file** (CLAUDE.md) for quick context
2. **Navigate to relevant docs** using links above
3. **Follow coding standards** (CODING_STANDARDS.md)
4. **Write code** following patterns from documentation
5. **Test thoroughly** (TESTING.md)

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
- **RabbitMQ**: http://localhost:15672 (user/password)

---

## 💡 Pro Tips

1. **Always read CODING_STANDARDS.md** before writing code
2. **All Docker commands** run from project root using `docker-compose.yml`
3. **Frontend state** managed by Pinia stores (no Vuex!)
4. **Backend layers**: Controller → Service → Repository → Entity
5. **TypeScript strict mode** - no `any` types allowed!
6. **Complete rebuild**: See DEVELOPMENT_WORKFLOW.md "Complete Project Rebuild"

---

## 📚 Learning Path (New AI Assistant)

If you're a new Claude Code instance or need complete context:

**Phase 1 - Essential (30 min)**
1. This file (CLAUDE.md) - Quick reference
2. docs/INDEX.md - Navigation map
3. docs/PROJECT_OVERVIEW.md - What we're building

**Phase 2 - Code Standards (45 min)**
4. docs/CODING_STANDARDS.md ⚠️ **MUST READ**

**Phase 3 - Core Architecture (30 min)**
5. docs/backend/ARCHITECTURE.md
6. docs/frontend/ARCHITECTURE.md

**Phase 4 - Development (30 min)**
7. docs/guides/DEVELOPMENT_WORKFLOW.md
8. docs/guides/TROUBLESHOOTING.md

**Total**: ~2 hours for complete context

---

**Last Updated**: 2025-01-05

**Documentation Version**: 1.0

**For questions or clarifications**: Refer to [`docs/INDEX.md`](docs/INDEX.md) for detailed navigation
- Когда реализуешь довольно глобальный и важный для проекта функционал - всегда обновляй полностью всю документацию в @docs/ а также по необходимости @CLAUDE.md
- Когда сталкиваешься с трудностями как пересобрать backend или frontend чтобы запустить тестировани (не важно - через bash скрипты или через mcp браузер) - всегда смотри в доку @docs/INDEX.md и оттуда в Development Workflow
- Делай коммит после каждого выполненного тобой промта - с понятным заголовком и супер минимальным описанием! И ГЛАВНОЕ ИМЕЙ ВВИДУ - ДЕЛАЙ КОММИТЫ ТОЛЬКО ТЕХ ИЗМЕНЕНИЙ КОТОРЫЕ СДЕЛАЛ ИМЕННО ТЫ, потому что параллельно с тобой могу работать я или другая нейронка - например курсор, и все происходит в одной ветке!
- Всегда когда пишешь какую-то документацию пиши ее нужной директории внутри @docs/ там уже решай куда ложить в корень @docs/ или в @docs/frontend/ @docs/backend/ @docs/guides/ , всегда обновляй @docs/INDEX.md и обновляй в нем нужную инфу и навигацию по документации
- Всегда смотри в доку @docs/* и предварительно изучай все файлы md и в подпапках все доки в md формате - чтобы обновлять контекст каждый раз (особенно когда я очищаю чат через /clear)
- не забывай делать коммит после любых изменениях которые ты вносишь и когда ты закончил работа (по моему промту) - сразу делай коммит! чтобы я потом в случае чего мог откатить его