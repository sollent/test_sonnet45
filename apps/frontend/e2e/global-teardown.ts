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
    // Локально НЕ останавливаем контейнеры и НЕ очищаем БД
    // Это позволяет разработчикам:
    // 1. Проверять данные после тестов
    // 2. Запускать тесты повторно без перезапуска контейнеров
    console.log('ℹ️  Локальная разработка: test окружение остается запущенным')
    console.log('ℹ️  Для очистки БД: docker exec test-backend-php83 php bin/console doctrine:schema:drop --force --env=test')
    console.log('ℹ️  Для повторного seeding: docker exec test-backend-php83 php bin/console app:e2e:seed --env=test')
    console.log('ℹ️  Для остановки: docker-compose -f infrastructure/docker/docker-compose.test.yml down')
  }
}

export default globalTeardown
