import { FullConfig } from '@playwright/test'
import { exec } from 'child_process'
import { promisify } from 'util'

const execAsync = promisify(exec)

async function globalSetup(config: FullConfig) {
  console.log('🚀 Запуск глобальной настройки E2E...')

  // 1. Проверка и запуск тестового окружения
  console.log('📦 Проверка тестовых Docker контейнеров...')

  try {
    // Проверяем что test окружение запущено
    await execAsync('docker ps | grep test-backend-php83')
    console.log('✅ Тестовое окружение уже запущено')
  } catch {
    console.log('⚠️  Тестовое окружение не запущено, запускаем...')
    await execAsync('docker-compose -f infrastructure/docker/docker-compose.test.yml --env-file .env.docker.test up -d')

    // Ожидание, пока сервисы станут healthy
    console.log('⏳ Ожидание готовности сервисов...')
    await execAsync('sleep 15')

    console.log('🗄️  Создание схемы базы данных...')
    // Используем doctrine:schema:create вместо миграций (безопаснее для test БД)
    await execAsync('docker exec test-backend-php83 php bin/console doctrine:database:create --if-not-exists --env=test')
    await execAsync('docker exec test-backend-php83 php bin/console doctrine:schema:create --env=test')
  }

  // 2. Заполнение базы данных тестовыми данными через Symfony команду
  console.log('🌱 Заполнение базы данных тестовыми данными...')

  try {
    // ВСЕГДА используем test backend для изоляции (локально и в CI)
    await execAsync('docker exec test-backend-php83 php bin/console app:e2e:seed --env=test')
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
