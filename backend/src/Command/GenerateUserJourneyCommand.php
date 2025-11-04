<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\Tag;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(
    name: 'app:generate-user-journey',
    description: 'Generate realistic 4-month user journey with tasks'
)]
class GenerateUserJourneyCommand extends Command
{
    // Огромная база русских названий задач по категориям
    private array $taskTemplates = [
        'development' => [
            'Разработать API для {feature}',
            'Исправить баг в {module}',
            'Код-ревью PR #{number}',
            'Рефакторинг {component}',
            'Оптимизировать запросы в {service}',
            'Написать тесты для {functionality}',
            'Обновить документацию {section}',
            'Настроить {tool} для проекта',
            'Интегрировать {library} в систему',
            'Провести анализ производительности {feature}',
            'Создать миграцию для {table}',
            'Добавить валидацию в {form}',
            'Реализовать {pattern} в {module}',
            'Настроить CI/CD для {environment}',
            'Деплой {version} на {server}',
        ],
        'meetings' => [
            'Встреча с командой разработки',
            'Созвон с клиентом {company}',
            'Планирование спринта #{number}',
            'Ретроспектива команды',
            'Daily standup',
            'Демо для стейкхолдеров',
            'Техническое интервью с {candidate}',
            'Обсуждение архитектуры {feature}',
            'Созвон с Product Owner',
            'Встреча с дизайнерами',
        ],
        'personal' => [
            'Купить {item} в магазине',
            'Записаться к {specialist}',
            'Оплатить {service}',
            'Забрать {item} из {place}',
            'Постирать и погладить {clothes}',
            'Убраться в {room}',
            'Приготовить {meal}',
            'Позвонить {person}',
            'Поздравить {friend} с {event}',
            'Починить {thing}',
            'Заказать {item} онлайн',
            'Записаться в {service}',
            'Навестить {relative}',
            'Отправить посылку в {city}',
            'Забронировать {service}',
        ],
        'health' => [
            'Пробежка {distance} км',
            'Тренировка в зале - {muscle_group}',
            'Йога и растяжка {duration} минут',
            'Медитация перед сном',
            'Плановый осмотр у {doctor}',
            'Сдать анализы ({type})',
            'Массаж спины',
            'Принять курс витаминов',
            'Бассейн - {laps} бассейнов',
            'Велопрогулка {distance} км',
            'Утренняя зарядка',
            'Запись к {specialist}',
        ],
        'learning' => [
            'Пройти урок {number} курса {course}',
            'Прочитать главу {chapter} книги "{book}"',
            'Посмотреть вебинар по {topic}',
            'Решить {number} задач на LeetCode',
            'Изучить документацию {technology}',
            'Написать статью про {topic}',
            'Практика {skill} - {duration} минут',
            'Изучить {design_pattern}',
            'Посмотреть конференцию {conference}',
            'Подготовиться к {certification}',
        ],
        'finance' => [
            'Оплатить {payment_type}',
            'Проверить счета и выписки',
            'Инвестировать {amount} рублей',
            'Пересмотреть бюджет на {month}',
            'Закрыть {account}',
            'Подать декларацию {type}',
            'Перевести деньги за {service}',
            'Проверить кэшбэк и бонусы',
            'Оплатить подписку {service}',
        ],
    ];

    private array $placeholders = [
        '{feature}' => ['аутентификации', 'уведомлений', 'профиля', 'чата', 'поиска', 'фильтров', 'экспорта', 'отчетов'],
        '{module}' => ['пользователей', 'заказов', 'платежей', 'доставки', 'каталога', 'корзины', 'отзывов'],
        '{component}' => ['UserService', 'OrderRepository', 'PaymentGateway', 'NotificationManager', 'CacheService'],
        '{service}' => ['AuthService', 'EmailService', 'StorageService', 'QueueService'],
        '{functionality}' => ['регистрации', 'авторизации', 'восстановления пароля', 'двухфакторной аутентификации'],
        '{section}' => ['API', 'базы данных', 'архитектуры', 'деплоя', 'безопасности'],
        '{tool}' => ['Docker', 'Kubernetes', 'Jenkins', 'GitLab CI', 'Prometheus', 'Grafana'],
        '{library}' => ['Symfony Mailer', 'Doctrine ORM', 'JWT Bundle', 'Symfony Serializer'],
        '{table}' => ['users', 'orders', 'products', 'payments', 'notifications'],
        '{form}' => ['регистрации', 'заказа', 'оплаты', 'обратной связи'],
        '{pattern}' => ['Repository', 'Factory', 'Strategy', 'Observer', 'Decorator'],
        '{environment}' => ['staging', 'production', 'development'],
        '{version}' => ['v1.2.3', 'v2.0.0', 'v1.5.1', 'v3.0.0-beta'],
        '{server}' => ['production', 'staging', 'test'],
        '{company}' => ['ООО "Альфа"', 'ИП Иванов', 'Корпорация "Омега"'],
        '{number}' => [1, 2, 3, 5, 8, 13, 21, 34],
        '{candidate}' => ['Иван Петров', 'Мария Сидорова', 'Алексей Козлов'],
        '{item}' => ['молоко', 'хлеб', 'овощи', 'фрукты', 'мясо', 'рыбу', 'бытовую химию', 'одежду'],
        '{specialist}' => ['терапевту', 'стоматологу', 'окулисту', 'кардиологу', 'дерматологу'],
        '{service}' => ['интернет', 'электричество', 'воду', 'газ', 'телефон', 'страховку'],
        '{place}' => ['почты', 'химчистки', 'ремонта', 'склада'],
        '{clothes}' => ['рубашки', 'джинсы', 'постельное белье', 'полотенца'],
        '{room}' => ['кухне', 'ванной', 'спальне', 'гостиной', 'балконе'],
        '{meal}' => ['борщ', 'пасту', 'салат', 'запеканку', 'пиццу', 'суп'],
        '{person}' => ['маме', 'папе', 'брату', 'сестре', 'другу', 'коллеге'],
        '{friend}' => ['Анну', 'Сергея', 'Марию', 'Дмитрия', 'Олега'],
        '{event}' => ['днем рождения', 'свадьбой', 'повышением', 'новосельем'],
        '{thing}' => ['кран', 'дверь', 'окно', 'стул', 'полку', 'розетку'],
        '{distance}' => [3, 5, 7, 10, 15],
        '{muscle_group}' => ['ноги', 'спина', 'грудь', 'руки', 'пресс'],
        '{duration}' => [20, 30, 45, 60],
        '{doctor}' => ['терапевта', 'стоматолога', 'кардиолога'],
        '{type}' => ['общий', 'биохимия', 'гормоны', 'УЗИ'],
        '{laps}' => [10, 20, 30, 40],
        '{course}' => ['Vue.js', 'React', 'Node.js', 'Python', 'Go', 'Kubernetes'],
        '{chapter}' => [1, 2, 3, 4, 5, 7, 10, 12],
        '{book}' => ['Clean Code', 'Design Patterns', 'Refactoring', 'Domain-Driven Design'],
        '{topic}' => ['микросервисов', 'GraphQL', 'WebAssembly', 'Machine Learning', 'DevOps'],
        '{technology}' => ['Docker', 'Kubernetes', 'PostgreSQL', 'Redis', 'RabbitMQ'],
        '{skill}' => ['TypeScript', 'Vue 3', 'английского языка', 'SQL'],
        '{design_pattern}' => ['Factory', 'Strategy', 'Observer', 'Singleton'],
        '{conference}' => ['Vue.js Conf', 'React Summit', 'JSNation', 'DevOops'],
        '{certification}' => ['AWS Certified', 'Kubernetes Admin', 'Scrum Master'],
        '{payment_type}' => ['ипотеку', 'кредит', 'налоги', 'штрафы'],
        '{amount}' => [5000, 10000, 15000, 20000, 50000],
        '{month}' => ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь'],
        '{account}' => ['депозит', 'кредитную карту', 'накопительный счет'],
        '{relative}' => ['бабушку', 'дедушку', 'тетю', 'дядю'],
        '{city}' => ['Москву', 'Питер', 'Казань', 'Екатеринбург'],
    ];

    private array $descriptions = [
        'Очень важная задача, требует внимания',
        'Необходимо завершить до конца недели',
        'Высокий приоритет, не откладывать',
        'Можно сделать в свободное время',
        'Требуется консультация со специалистом',
        'Долгосрочная задача, разбить на этапы',
        'Быстрая задача, займет 15-20 минут',
        'Рутинная задача, делаю регулярно',
        'Экспериментальная задача',
        'Нужна помощь коллег',
        'Критически важно для проекта',
        'Можно делегировать',
        'Требует креативного подхода',
        'Техническая задача',
        'Организационная задача',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🚀 ГЕНЕРАЦИЯ РЕАЛИСТИЧНОГО ПУТИ ПОЛЬЗОВАТЕЛЯ ЗА 4 МЕСЯЦА');
        
        // Find user
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'sophiekhouryna@gmail.com']);

        if (!$user) {
            $io->error('❌ Пользователь sophiekhouryna@gmail.com не найден!');
            return Command::FAILURE;
        }

        $io->success("✅ Пользователь: {$user->getEmail()}");

        // Очистка старых данных
        $io->section('Очистка старых данных...');
        $this->cleanUserData($user);
        $io->success('✅ Данные очищены');

        // Создание тегов
        $tags = $this->createTags($user, $io);

        // Период генерации
        $startDate = new \DateTime('2025-07-01 00:00:00'); // С 1 июля
        $endDate = new \DateTime('now');
        $totalDays = (int)$startDate->diff($endDate)->days;

        $io->section('📊 Параметры генерации:');
        $io->listing([
            "Период: {$startDate->format('d.m.Y')} - {$endDate->format('d.m.Y')}",
            "Всего дней: {$totalDays}",
            'Задач в день: 40-80 (варьируется)',
            'Подзадачи: 30% задач имеют 1-4 подзадачи',
            'Завершение: 60-70% задач завершаются',
            'Разнообразие: максимальное',
        ]);

        $io->newLine();
        $progressBar = new ProgressBar($output, $totalDays);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | День: %message%');
        $progressBar->start();

        $totalTasks = 0;
        $totalSubtasks = 0;
        $completedCount = 0;
        $currentDate = clone $startDate;
        $batchSize = 100;

        $userProductivity = []; // Трекинг продуктивности по дням

        while ($currentDate <= $endDate) {
            $dayKey = $currentDate->format('Y-m-d');
            $dayOfWeek = (int)$currentDate->format('N');
            $isWeekend = in_array($dayOfWeek, [6, 7]);
            
            // Определяем количество задач на день
            $tasksPerDay = $this->calculateDailyTasks($dayOfWeek, $currentDate);
            
            $dailyCreated = 0;
            $dailyCompleted = 0;

            for ($i = 0; $i < $tasksPerDay; $i++) {
                // Создаем основную задачу
                $task = $this->createRealisticTask(
                    $user, 
                    $tags, 
                    $currentDate, 
                    $isWeekend, 
                    $dayOfWeek,
                    $i
                );

                $this->entityManager->persist($task);
                $totalTasks++;
                $dailyCreated++;

                if ($task->getStatus() === TaskStatus::COMPLETED) {
                    $completedCount++;
                    $dailyCompleted++;
                }

                // Создаем подзадачи (30% шанс)
                if (mt_rand(1, 100) <= 30) {
                    $subtasksCount = $this->createSubtasksForTask(
                        $task,
                        $user,
                        $tags,
                        $currentDate,
                        $endDate
                    );
                    $totalSubtasks += $subtasksCount;
                }

                // Batch flush
                if ($totalTasks % $batchSize === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $user = $userRepository->find($user->getId());
                    $tags = $this->reloadTags($tags);
                }
            }

            $userProductivity[$dayKey] = [
                'created' => $dailyCreated,
                'completed' => $dailyCompleted,
            ];

            $progressBar->setMessage($currentDate->format('d.m.Y'));
            $progressBar->advance();
            $currentDate->modify('+1 day');
        }

        // Final flush
        $this->entityManager->flush();
        $progressBar->finish();

        $io->newLine(2);
        $io->success('🎉 ГЕНЕРАЦИЯ ЗАВЕРШЕНА!');

        // Статистика
        $this->showStatistics($io, $totalTasks, $totalSubtasks, $completedCount, $totalDays, $userProductivity);

        return Command::SUCCESS;
    }

    private function calculateDailyTasks(int $dayOfWeek, \DateTime $date): int
    {
        $baseAmount = 60;

        // День недели
        $weekdayMultiplier = [
            1 => 1.2,  // Понедельник - много планирования
            2 => 1.1,  // Вторник
            3 => 1.0,  // Среда
            4 => 1.0,  // Четверг
            5 => 0.9,  // Пятница - меньше
            6 => 0.5,  // Суббота - личные дела
            7 => 0.4,  // Воскресенье - отдых
        ];

        $tasks = (int)($baseAmount * ($weekdayMultiplier[$dayOfWeek] ?? 1.0));

        // Случайный разброс ±20%
        $variance = mt_rand(-20, 20);
        $tasks = $tasks + (int)($tasks * $variance / 100);

        // Первая неделя месяца - обычно больше планирования
        if ((int)$date->format('d') <= 7) {
            $tasks = (int)($tasks * 1.15);
        }

        // Последняя неделя месяца - дедлайны
        if ((int)$date->format('d') >= 25) {
            $tasks = (int)($tasks * 1.1);
        }

        return max($tasks, 20); // Минимум 20 задач в день
    }

    private function createRealisticTask(
        User $user,
        array $tags,
        \DateTime $currentDate,
        bool $isWeekend,
        int $dayOfWeek,
        int $taskIndex
    ): Task {
        $task = new Task();

        // Выбираем категорию
        $category = $this->selectCategory($isWeekend, $dayOfWeek);
        $template = $this->taskTemplates[$category][array_rand($this->taskTemplates[$category])];
        
        // Заполняем плейсхолдеры
        $title = $this->fillPlaceholders($template);
        $task->setTitle($title);

        // Описание (60% задач)
        if (mt_rand(1, 100) <= 60) {
            $description = $this->descriptions[array_rand($this->descriptions)];
            
            // Добавляем детали
            if (mt_rand(1, 100) <= 40) {
                $details = [
                    'Deadline: ' . (clone $currentDate)->modify('+' . mt_rand(1, 14) . ' days')->format('d.m.Y'),
                    'Ожидаемое время: ' . mt_rand(15, 240) . ' минут',
                    'Зависит от: задача #' . mt_rand(1, 1000),
                    'Результат: ' . ['документ', 'код', 'отчет', 'презентация'][mt_rand(0, 3)],
                ];
                $description .= "\n\n" . $details[array_rand($details)];
            }
            
            $task->setDescription($description);
        }

        // Приоритет
        $priority = $this->selectPriority($category, $dayOfWeek);
        $task->setPriority($priority);

        // Время создания
        $hour = $this->getRealisticHour($isWeekend, $category);
        $createdAt = clone $currentDate;
        $createdAt->setTime($hour, mt_rand(0, 59), mt_rand(0, 59));

        // Устанавливаем даты
        $reflection = new \ReflectionClass($task);
        $createdAtProp = $reflection->getProperty('createdAt');
        $createdAtProp->setAccessible(true);
        $createdAtProp->setValue($task, \DateTimeImmutable::createFromMutable($createdAt));
        
        $updatedAtProp = $reflection->getProperty('updatedAt');
        $updatedAtProp->setAccessible(true);
        $updatedAtProp->setValue($task, \DateTimeImmutable::createFromMutable($createdAt));

        // Start Date (70%)
        if (mt_rand(1, 100) <= 70) {
            $startDate = clone $createdAt;
            $startDate->modify('+' . mt_rand(0, 3) . ' days');
            $task->setStartDate(\DateTimeImmutable::createFromMutable($startDate));
        }

        // Due Date (85%)
        if (mt_rand(1, 100) <= 85) {
            $dueDays = match($priority) {
                TaskPriority::URGENT => mt_rand(1, 3),
                TaskPriority::HIGH => mt_rand(2, 7),
                TaskPriority::MEDIUM => mt_rand(5, 14),
                TaskPriority::LOW => mt_rand(7, 21),
            };
            
            $dueDate = clone $createdAt;
            $dueDate->modify("+{$dueDays} days");
            $task->setDueDate(\DateTimeImmutable::createFromMutable($dueDate));
        }

        // Статус и завершение
        $this->setTaskStatus($task, $createdAt, new \DateTime('now'));

        // Теги
        $this->assignTags($task, $tags, $category, $priority);

        $task->setUser($user);
        $task->setSortOrder(mt_rand(0, 1000));
        $task->setIsArchived(mt_rand(1, 100) <= 3); // 3% архивировано

        return $task;
    }

    private function createSubtasksForTask(
        Task $parentTask,
        User $user,
        array $tags,
        \DateTime $baseDate,
        \DateTime $endDate
    ): int {
        $numSubtasks = mt_rand(1, 4);
        $completedSubtasks = 0;

        $subtaskTitles = [
            'Подготовительный этап',
            'Исследование и анализ',
            'Разработка решения',
            'Тестирование',
            'Документирование',
            'Code review',
            'Деплой и мониторинг',
            'Сбор требований',
            'Проектирование',
            'Реализация',
            'Оптимизация',
            'Финальная проверка',
        ];

        for ($i = 0; $i < $numSubtasks; $i++) {
            $subtask = new Task();
            $subtask->setTitle($subtaskTitles[array_rand($subtaskTitles)] . ' - часть ' . ($i + 1));
            $subtask->setUser($user);
            $subtask->setParentTask($parentTask);
            $subtask->setPriority($parentTask->getPriority());

            // Статус подзадачи - часть завершена, часть нет
            $statusRand = mt_rand(1, 100);
            if ($statusRand <= 50) {
                $subtask->setStatus(TaskStatus::COMPLETED);
                
                $completedAt = clone $baseDate;
                $completedAt->modify('+' . mt_rand(1, 7) . ' days');
                if ($completedAt <= $endDate) {
                    $subtask->setCompletedAt(\DateTimeImmutable::createFromMutable($completedAt));
                    $completedSubtasks++;
                }
            } elseif ($statusRand <= 80) {
                $subtask->setStatus(TaskStatus::IN_PROGRESS);
            } else {
                $subtask->setStatus(TaskStatus::PENDING);
            }

            // Даты
            $subCreatedAt = clone $baseDate;
            $subCreatedAt->modify('+' . ($i * 30) . ' minutes');
            
            $reflection = new \ReflectionClass($subtask);
            $createdAtProp = $reflection->getProperty('createdAt');
            $createdAtProp->setAccessible(true);
            $createdAtProp->setValue($subtask, \DateTimeImmutable::createFromMutable($subCreatedAt));
            
            $updatedAtProp = $reflection->getProperty('updatedAt');
            $updatedAtProp->setAccessible(true);
            $updatedAtProp->setValue($subtask, \DateTimeImmutable::createFromMutable($subCreatedAt));

            $this->entityManager->persist($subtask);
        }

        return $numSubtasks;
    }

    private function selectCategory(bool $isWeekend, int $dayOfWeek): string
    {
        if ($isWeekend) {
            $weights = [
                'development' => 10,
                'meetings' => 5,
                'personal' => 50,
                'health' => 25,
                'learning' => 5,
                'finance' => 5,
            ];
        } else {
            $weights = [
                'development' => 45,
                'meetings' => 20,
                'personal' => 15,
                'health' => 10,
                'learning' => 5,
                'finance' => 5,
            ];
        }

        return $this->weightedRandom($weights);
    }

    private function selectPriority(string $category, int $dayOfWeek): TaskPriority
    {
        // Понедельник - больше urgent
        if ($dayOfWeek === 1) {
            $rand = mt_rand(1, 100);
            if ($rand <= 15) return TaskPriority::URGENT;
            if ($rand <= 40) return TaskPriority::HIGH;
            if ($rand <= 75) return TaskPriority::MEDIUM;
            return TaskPriority::LOW;
        }

        // Обычное распределение
        $rand = mt_rand(1, 100);
        if ($rand <= 8) return TaskPriority::URGENT;
        if ($rand <= 25) return TaskPriority::HIGH;
        if ($rand <= 65) return TaskPriority::MEDIUM;
        return TaskPriority::LOW;
    }

    private function setTaskStatus(Task $task, \DateTime $createdAt, \DateTime $now): void
    {
        $daysSinceCreation = (int)$createdAt->diff($now)->days;

        // Логика завершения реалистичная
        $completionChance = 0;
        
        if ($daysSinceCreation > 30) {
            $completionChance = 75; // Старые задачи обычно завершены
        } elseif ($daysSinceCreation > 14) {
            $completionChance = 60;
        } elseif ($daysSinceCreation > 7) {
            $completionChance = 45;
        } elseif ($daysSinceCreation > 3) {
            $completionChance = 30;
        } else {
            $completionChance = 15; // Свежие задачи редко завершены
        }

        // Priority влияет на завершение
        if ($task->getPriority() === TaskPriority::URGENT) {
            $completionChance += 20; // Срочные чаще завершаются
        } elseif ($task->getPriority() === TaskPriority::LOW) {
            $completionChance -= 15; // Низкие откладываются
        }

        $rand = mt_rand(1, 100);

        if ($rand <= $completionChance) {
            $task->setStatus(TaskStatus::COMPLETED);
            
            // Время завершения (1-10 дней после создания)
            $completedAt = clone $createdAt;
            $daysToComplete = mt_rand(1, min(10, $daysSinceCreation));
            $completedAt->modify("+{$daysToComplete} days");
            $completedAt->setTime(mt_rand(9, 20), mt_rand(0, 59));
            
            if ($completedAt <= $now) {
                $task->setCompletedAt(\DateTimeImmutable::createFromMutable($completedAt));
            }
        } elseif ($rand <= $completionChance + 25) {
            $task->setStatus(TaskStatus::IN_PROGRESS);
        } elseif ($rand <= $completionChance + 45) {
            $task->setStatus(TaskStatus::PENDING);
        } else {
            $task->setStatus(TaskStatus::CANCELLED);
        }
    }

    private function assignTags(Task $task, array $tags, string $category, TaskPriority $priority): void
    {
        $categoryTagMap = [
            'development' => 'Работа',
            'meetings' => 'Работа',
            'personal' => 'Личное',
            'health' => 'Здоровье',
            'learning' => 'Обучение',
            'finance' => 'Финансы',
        ];

        // Основной тег категории
        if (isset($categoryTagMap[$category]) && isset($tags[$categoryTagMap[$category]])) {
            $task->addTag($tags[$categoryTagMap[$category]]);
        }

        // Дополнительные теги (1-2)
        $numAdditional = mt_rand(1, 2);
        $availableTags = array_values($tags);
        shuffle($availableTags);
        
        $added = 0;
        foreach ($availableTags as $tag) {
            if ($added >= $numAdditional) break;
            if (!$task->getTags()->contains($tag)) {
                $task->addTag($tag);
                $added++;
            }
        }

        // Urgent задачи получают тег "Срочно"
        if ($priority === TaskPriority::URGENT && isset($tags['Срочно'])) {
            if (!$task->getTags()->contains($tags['Срочно'])) {
                $task->addTag($tags['Срочно']);
            }
        }
    }

    private function fillPlaceholders(string $template): string
    {
        foreach ($this->placeholders as $placeholder => $options) {
            if (strpos($template, $placeholder) !== false) {
                $value = $options[array_rand($options)];
                $template = str_replace($placeholder, (string)$value, $template);
            }
        }
        
        return $template;
    }

    private function getRealisticHour(bool $isWeekend, string $category): int
    {
        if ($isWeekend) {
            return mt_rand(9, 21); // Выходные - позже встают
        }

        // Будни - зависит от категории
        if ($category === 'development' || $category === 'meetings') {
            return mt_rand(9, 18); // Рабочие часы
        }

        return mt_rand(7, 22); // Личные дела - в любое время
    }

    private function weightedRandom(array $weights): string
    {
        $rand = mt_rand(1, array_sum($weights));
        $sum = 0;
        
        foreach ($weights as $item => $weight) {
            $sum += $weight;
            if ($rand <= $sum) {
                return $item;
            }
        }

        return array_key_first($weights);
    }

    private function createTags(User $user, SymfonyStyle $io): array
    {
        $io->section('Создание тегов...');
        
        $tagRepository = $this->entityManager->getRepository(Tag::class);
        $tagData = [
            'Работа' => '#6366f1',
            'Личное' => '#10b981',
            'Финансы' => '#f59e0b',
            'Здоровье' => '#ef4444',
            'Обучение' => '#8b5cf6',
            'Хобби' => '#ec4899',
            'Дом' => '#06b6d4',
            'Срочно' => '#dc2626',
            'Важно' => '#f97316',
            'Спорт' => '#22c55e',
            'Семья' => '#a855f7',
            'Покупки' => '#eab308',
            'Проект А' => '#3b82f6',
            'Проект Б' => '#8b5cf6',
            'Планирование' => '#14b8a6',
        ];
        
        $tags = [];
        foreach ($tagData as $name => $color) {
            $tag = new Tag();
            $tag->setName($name);
            $tag->setColor($color);
            $tag->setUser($user);
            $this->entityManager->persist($tag);
            $tags[$name] = $tag;
        }
        
        $this->entityManager->flush();
        $io->success('✅ Создано ' . count($tags) . ' тегов');
        
        return $tags;
    }

    private function cleanUserData(User $user): void
    {
        $connection = $this->entityManager->getConnection();
        
        // Удаляем задачи
        $connection->executeStatement(
            'DELETE FROM task WHERE user_id = :userId',
            ['userId' => $user->getId()]
        );
        
        // Удаляем теги
        $connection->executeStatement(
            'DELETE FROM tag WHERE user_id = :userId',
            ['userId' => $user->getId()]
        );
    }

    private function reloadTags(array $tags): array
    {
        $tagRepository = $this->entityManager->getRepository(Tag::class);
        $reloaded = [];
        
        foreach ($tags as $name => $tag) {
            $reloaded[$name] = $tagRepository->find($tag->getId());
        }
        
        return $reloaded;
    }

    private function showStatistics(
        SymfonyStyle $io,
        int $totalTasks,
        int $totalSubtasks,
        int $completedCount,
        int $totalDays,
        array $productivity
    ): void {
        $io->section('📊 Детальная статистика:');
        
        $io->table(
            ['Метрика', 'Значение'],
            [
                ['Всего задач', number_format($totalTasks, 0, '.', ' ')],
                ['Подзадач создано', number_format($totalSubtasks, 0, '.', ' ')],
                ['Завершено задач', number_format($completedCount, 0, '.', ' ')],
                ['Процент завершения', round($completedCount / $totalTasks * 100, 1) . '%'],
                ['Дней в периоде', $totalDays],
                ['Среднее задач/день', round($totalTasks / $totalDays, 1)],
            ]
        );

        // Лучшие и худшие дни
        uasort($productivity, fn($a, $b) => $b['created'] <=> $a['created']);
        $bestDays = array_slice($productivity, 0, 5, true);
        
        $io->section('🏆 Топ-5 самых продуктивных дней:');
        foreach ($bestDays as $date => $stats) {
            $io->text("📅 {$date}: {$stats['created']} создано, {$stats['completed']} завершено");
        }

        $io->success('✨ Данные готовы! Теперь графики покажут реальную картину!');
    }
}



