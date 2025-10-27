# 🚀 Full-Stack Authentication Boilerplate

A production-ready full-stack authentication boilerplate with JWT, Google OAuth2, multi-language support, and admin panel.

## ✨ Features

- 🔐 **JWT Authentication** with refresh tokens
- 🌐 **Google OAuth2** integration  
- 🌍 **Multi-language support** (Russian/English)
- 👨‍💼 **Admin Panel** with EasyAdmin
- 📱 **Responsive UI** with PrimeVue
- 🧪 **Full test coverage** (Unit, Functional, Integration)
- 🐳 **Docker** containerization
- 📝 **TypeScript** for type safety

## 🛠 Tech Stack

**Backend:** Symfony 7.1, PHP 8.3, PostgreSQL 16, JWT, Google OAuth2, EasyAdmin  
**Frontend:** Vue.js 3, TypeScript, Vite, PrimeVue, Pinia, Vue I18n  
**Infrastructure:** Docker, Nginx, RabbitMQ

## 📚 Documentation

For complete documentation, architecture details, API reference, and setup instructions, please see:

**[📖 PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md)**

## 🚀 Quick Start

```bash
# Start backend
docker-compose up -d

# Setup database
docker exec backend-php83 php bin/console doctrine:migrations:migrate

# Install frontend
cd frontend && npm install

# Start frontend
npm run dev
```

Access points:
- Frontend: http://localhost:3000
- Backend API: http://localhost:8089/api
- Admin Panel: http://localhost:8089/admin

## 📄 License

MIT

---

*Use this boilerplate as a foundation for your next full-stack application!*