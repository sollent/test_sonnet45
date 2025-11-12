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

    console.log('🗄️  Создание базы данных (если не существует)...')
    // Примечание: Symfony добавит суффикс _test, поэтому backend_test станет backend_test_test
    await execAsync(
      'docker exec ' +
      '-e "DATABASE_URL=postgresql://test_user:test_password@test-psql16:5432/backend_test?serverVersion=16&charset=utf8" ' +
      'test-backend-php83 php bin/console doctrine:database:create --if-not-exists --env=test'
    )
  }

  // 2. Очистка и пересоздание схемы БД (для свежих данных каждый раз)
  console.log('🧹 Очистка тестовой базы данных...')

  try {
    // Удаляем схему БД (все таблицы и данные)
    // Примечание: Symfony автоматически добавляет "_test" к имени БД, поэтому backend_test станет backend_test_test
    await execAsync(
      'docker exec ' +
      '-e "DATABASE_URL=postgresql://test_user:test_password@test-psql16:5432/backend_test?serverVersion=16&charset=utf8" ' +
      'test-backend-php83 php bin/console doctrine:schema:drop --force --full-database --env=test'
    )
    console.log('✅ Старые данные удалены')

    // Создаем схему заново
    console.log('🗄️  Создание свежей схемы базы данных...')
    await execAsync(
      'docker exec ' +
      '-e "DATABASE_URL=postgresql://test_user:test_password@test-psql16:5432/backend_test?serverVersion=16&charset=utf8" ' +
      'test-backend-php83 php bin/console doctrine:schema:create --env=test'
    )
    console.log('✅ Схема БД создана')
  } catch (error) {
    console.error('❌ Не удалось пересоздать схему БД:', error)
    throw error
  }

  // 3. Заполнение базы данных тестовыми данными через Symfony команду
  console.log('🌱 Заполнение базы данных тестовыми данными...')

  try {
    // ВСЕГДА используем test backend для изоляции (локально и в CI)
    // Примечание: backend_test станет backend_test_test после добавления суффикса Symfony
    await execAsync(
      'docker exec ' +
      '-e "DATABASE_URL=postgresql://test_user:test_password@test-psql16:5432/backend_test?serverVersion=16&charset=utf8" ' +
      'test-backend-php83 php bin/console app:e2e:seed --env=test'
    )
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
