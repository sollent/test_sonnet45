<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\Database\UserRepository;
use App\Service\AI\VoiceProcessingService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-voice-commands',
    description: 'Test voice AI assistant with complex commands',
)]
class TestVoiceCommandsCommand extends Command
{
    public function __construct(
        private VoiceProcessingService $voiceProcessingService,
        private UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Получаем тестового пользователя
        $user = $this->userRepository->findOneBy(['email' => 'harris.maude@example.net']);

        if (!$user) {
            $io->error('Пользователь не найден!');

            return Command::FAILURE;
        }

        $io->success("Пользователь найден: {$user->getEmail()}");
        $io->newLine();

        // Массив тестовых команд
        $testCommands = [
            // 1. Простая команда
            [
                'name' => 'Простая задача',
                'text' => 'Создай задачу купить молоко на завтра',
            ],

            // 2. Создание 2-3 задач одновременно (ТВОЙ ПРИМЕР!)
            [
                'name' => 'Множественные задачи (ТВОЙ ПРИМЕР!)',
                'text' => 'Сделай задачку на сегодня сходить в магазин с женой и детьми с 19:00 - 20:00 и задачку на следующий понедельник купить сцепление для мерса и пометь ее как важную',
            ],

            // 3. Задача с подзадачами
            [
                'name' => 'Задача с подзадачами',
                'text' => 'Создай задачу ремонт квартиры на следующую неделю с подзадачами: купить краску, нанять мастера, убрать мебель',
            ],

            // 4. Изменение приоритета задачи
            [
                'name' => 'Изменение приоритета',
                'text' => 'Сделай задачу купить молоко важной',
            ],
        ];

        // Запускаем тесты
        foreach ($testCommands as $index => $testCommand) {
            $num = $index + 1;

            $io->section("ТЕСТ #{$num}: {$testCommand['name']}");
            $io->text("📝 Команда: \"{$testCommand['text']}\"");
            $io->newLine();

            try {
                $startTime = microtime(true);

                // Обрабатываем команду
                $command = $this->voiceProcessingService->processVoiceText($testCommand['text'], $user);

                $duration = round((microtime(true) - $startTime) * 1000);

                // Выводим результат
                $io->success("Команда обработана за {$duration}ms");
                $io->text("📊 Статус: {$command->getStatus()->value}");
                $io->text("🔍 Распознанный текст: {$command->getTranscribedText()}");

                if ($command->getParsedCommand()) {
                    $parsed = $command->getParsedCommand();
                    $io->text("🎯 Распознанное действие: {$parsed['action']}");
                    $io->text('🔧 Параметры:');
                    $io->block(json_encode($parsed['parameters'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }

                if ($command->getExecutionResult()) {
                    $result = $command->getExecutionResult();
                    $io->text('📦 Результат выполнения:');
                    $io->block(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }

                if ($command->getErrorMessage()) {
                    $io->warning("⚠️  Ошибка: {$command->getErrorMessage()}");
                }

            } catch (Exception $e) {
                $io->error("ОШИБКА: {$e->getMessage()}");
                $io->block($e->getTraceAsString());
            }

            $io->newLine();
        }

        $io->success('Все тесты завершены!');

        return Command::SUCCESS;
    }
}
