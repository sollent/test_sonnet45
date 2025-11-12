import { FullConfig } from '@playwright/test'
import { exec } from 'child_process'
import { promisify } from 'util'

const execAsync = promisify(exec)

async function globalTeardown(config: FullConfig) {
  console.log('\n🧹 Запуск глобальной очистки E2E...')

  if (process.env.CI) {
    // В CI останавливаем и удаляем тестовые контейнеры и volumes
    console.log('🛑 Остановка тестовых Docker контейнеров...')
    await execAsync('docker-compose -f infrastructure/docker/docker-compose.test.yml down -v')
    console.log('✅ Тестовое окружение очищено')
  } else {
    // Локально НЕ очищаем базу данных
    // Это позволяет разработчикам проверять данные после тестов
    console.log('ℹ️  Локальная разработка: БД не очищается')
    console.log('ℹ️  Для повторного seeding запустите: docker exec backend-php83 php bin/console app:e2e:seed')
  }
}

export default globalTeardown
