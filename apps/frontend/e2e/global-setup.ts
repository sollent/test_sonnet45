import { FullConfig } from '@playwright/test'
import { exec } from 'child_process'
import { promisify } from 'util'

const execAsync = promisify(exec)

async function globalSetup(config: FullConfig) {
  console.log('🚀 Запуск глобальной настройки E2E...')

  // 1. Запуск тестового окружения (если еще не запущено) - только в CI
  if (process.env.CI) {
    console.log('📦 Запуск тестовых Docker контейнеров...')
    await execAsync('docker-compose -f infrastructure/docker/docker-compose.test.yml up -d')

    // Ожидание, пока сервисы станут healthy
    console.log('⏳ Ожидание готовности сервисов...')
    await execAsync('sleep 10')

    console.log('🗄️  Запуск миграций...')
    await execAsync('docker-compose -f infrastructure/docker/docker-compose.test.yml exec -T test-backend php bin/console doctrine:migrations:migrate --no-interaction')
  }

  // 2. Заполнение базы данных тестовыми данными через Symfony команду
  console.log('🌱 Заполнение базы данных тестовыми данными...')

  try {
    if (process.env.CI) {
      // В CI используем Docker test environment
      await execAsync('docker-compose -f infrastructure/docker/docker-compose.test.yml exec -T test-backend php bin/console app:e2e:seed')
    } else {
      // Локально используем dev backend
      await execAsync('docker exec backend-php83 php bin/console app:e2e:seed')
    }
    console.log('✅ Тестовые данные успешно заполнены')
  } catch (error) {
    console.error('❌ Не удалось заполнить тестовые данные:', error)
    throw error
  }

  console.log('✅ Глобальная настройка завершена\n')
  console.log('📊 Созданные тестовые данные:')
  console.log('   👤 1 тестовый пользователь (e2e-test@example.com)')
  console.log('   📝 10 задач (с различными статусами, приоритетами, датами)')
  console.log('   🔁 4 повторяющиеся задачи (daily, weekly, monthly, yearly)')
  console.log('   🏷️  5 тегов')
  console.log('   🌲 1 родительская задача + 1 подзадача')
  console.log('')
}

export default globalSetup
