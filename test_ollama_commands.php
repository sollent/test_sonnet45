#!/usr/bin/env php
<?php

/**
 * Тестовый скрипт для проверки работы LLM с оптимизированным SYSTEM_PROMPT
 * Запуск: php test_ollama_commands.php
 */

// Оптимизированный SYSTEM_PROMPT (версия 2.0)
$systemPrompt = <<<'PROMPT'
Ты - ассистент управления задачами. Анализируй голосовые команды на русском и возвращай JSON.

КРИТИЧЕСКИ ВАЖНО:
1. Возвращай ТОЛЬКО валидный JSON без пояснений и комментариев
2. ИЗВЛЕКАЙ дату/время из текста и помещай в отдельные параметры (due_date, start_time, end_time)
3. ИСПРАВЛЯЙ опечатки и грамматические ошибки, но СОХРАНЯЙ смысл
4. НЕ ПЕРЕФРАЗИРУЙ title сильно - используй слова пользователя, только исправь ошибки
5. Понимай команды даже с пропущенными запятыми и неправильными окончаниями

ПРАВИЛА ДЛЯ TITLE:
- МИНИМАЛЬНАЯ переформулировка! Сохраняй оригинальные слова пользователя
- "записываться гдоктору" → "Записаться к доктору" (НЕ "Запись к врачу"!)
- "купить свиноматку" → "Купить свиноматку" (сохраняй как есть!)
- Исправляй только явные опечатки: "гдоктору" → "к доктору"
- НЕ заменяй слова на синонимы без необходимости

=== СТАНДАРТИЗАЦИЯ ДАТ (ВАЖНО!) ===

ВСЕГДА используй ТОЛЬКО эти форматы для due_date:
- "today" - сегодня, на сегодня, сегодняшний
- "tomorrow" - завтра, на завтра, завтрашний
- "day_after_tomorrow" - послезавтра, через день
- "next_week" - через неделю, на следующей неделе
- "next_month" - через месяц, в следующем месяце
- "monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday" - дни недели
- "this_week" - на этой неделе, эта неделя
- "2024-12-25" - конкретные даты в формате YYYY-MM-DD

ВРЕМЯ (start_time, end_time) ВСЕГДА в формате:
- "HH:MM" - например "14:00", "09:30", "21:00"
- НЕ используй "14 часов", "два часа дня" - только "14:00"
- "утром" → "09:00", "днем" → "14:00", "вечером" → "19:00"

=== ДОСТУПНЫЕ ДЕЙСТВИЯ ===

1. create_task - создать одну задачу
   Параметры: title, due_date, start_time, end_time, priority (low/medium/high/urgent), tags

2. create_multiple_tasks - создать несколько задач
   Параметры: tasks: массив задач

3. complete_task - завершить задачу
   Параметры: search

4. uncomplete_task - отменить завершение
   Параметры: search

5. update_task - изменить приоритет/статус/дату/время
   Параметры: search, updates: {priority, status (pending/in_progress/completed), due_date, start_time, end_time}

6. filter_tasks - показать/найти задачи
   Параметры: filters: {date, priority, status, search}

7. create_subtask - создать подзадачу
   Параметры: parent_search, title

8. move_task - перенести задачу
   Параметры: search, new_date, start_time, end_time

9. bulk_complete - завершить несколько задач
   Параметры: filters

ФОРМАТ ОТВЕТА:
{"action":"название_действия","parameters":{...},"confidence":0.0-1.0}

Теперь обработай команду:
PROMPT;

// Тестовые команды
$testCommands = [
    // Создание задач с разными датами
    "Создай задачу купить молоко на сегодня",
    "Создай задачу позвонить клиенту на завтра в 14:00",
    "Создай срочную задачу написать отчет на послезавтра",
    "Создай задачу встреча в понедельник с 10:00 до 11:30",

    // Множественные задачи
    "Создай две задачи: одна купить хлеб на сегодня, вторая позвонить маме завтра",

    // Статусы и приоритеты
    "Переведи задачу отчет в статус в работе",
    "Сделай задачу встреча срочной",

    // Завершение и отмена
    "Завершить задачу написать отчет",
    "Верни задачу тренировка в работу",

    // Перенос задач
    "Перенеси встречу на послезавтра в 16:00",

    // Фильтрация
    "Покажи срочные задачи в работе",
    "Найди все задачи на сегодня",

    // С опечатками
    "Завиршить задачу отчот",
    "Создает задачу купить свиноматку на сегоня",
];

// Функция для вызова Ollama
function callOllama($prompt, $command) {
    $fullPrompt = $prompt . "\n\nКоманда: \"" . $command . '"';

    $data = [
        'model' => 'qwen2.5:14b',
        'prompt' => $fullPrompt,
        'stream' => false,
        'format' => 'json',
        'options' => [
            'temperature' => 0.1,
            'top_p' => 0.9,
            'num_predict' => 256,
        ],
    ];

    $ch = curl_init('http://localhost:11434/api/generate');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => 'HTTP ' . $httpCode];
    }

    $result = json_decode($response, true);

    if (!isset($result['response'])) {
        return ['error' => 'Invalid response'];
    }

    // Извлекаем JSON из ответа
    $text = $result['response'];
    $jsonStart = strpos($text, '{');
    $jsonEnd = strrpos($text, '}');

    if ($jsonStart === false || $jsonEnd === false) {
        return ['error' => 'No JSON found', 'raw' => $text];
    }

    $jsonString = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
    $parsed = json_decode($jsonString, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Invalid JSON: ' . json_last_error_msg(), 'raw' => $jsonString];
    }

    return $parsed;
}

// Цвета для вывода
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m",
];

// Проверяем доступность Ollama
echo $colors['cyan'] . "🔍 Проверка доступности Ollama...\n" . $colors['reset'];
$ch = curl_init('http://localhost:11434/api/tags');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo $colors['red'] . "❌ Ollama недоступен! Запустите: ollama serve\n" . $colors['reset'];
    exit(1);
}

echo $colors['green'] . "✅ Ollama доступен\n\n" . $colors['reset'];

// Тестируем команды
$successCount = 0;
$failCount = 0;

foreach ($testCommands as $index => $command) {
    echo $colors['blue'] . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Тест #" . ($index + 1) . ": " . $command . "\n" . $colors['reset'];

    $result = callOllama($systemPrompt, $command);

    if (isset($result['error'])) {
        echo $colors['red'] . "❌ Ошибка: " . $result['error'] . "\n";
        if (isset($result['raw'])) {
            echo "Raw: " . substr($result['raw'], 0, 200) . "...\n";
        }
        echo $colors['reset'];
        $failCount++;
        continue;
    }

    // Проверяем результат
    $isValid = true;
    $errors = [];

    // Проверка обязательных полей
    if (!isset($result['action'])) {
        $errors[] = "Отсутствует action";
        $isValid = false;
    }

    if (!isset($result['confidence'])) {
        $errors[] = "Отсутствует confidence";
        $isValid = false;
    }

    // Проверка форматов дат
    if (isset($result['parameters']['due_date'])) {
        $validDates = ['today', 'tomorrow', 'day_after_tomorrow', 'next_week', 'next_month', 'this_week',
                      'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $dueDate = $result['parameters']['due_date'];

        if (!in_array($dueDate, $validDates) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $errors[] = "Некорректный формат due_date: " . $dueDate;
            $isValid = false;
        }
    }

    // Проверка формата времени
    if (isset($result['parameters']['start_time'])) {
        if (!preg_match('/^\d{1,2}:\d{2}$/', $result['parameters']['start_time'])) {
            $errors[] = "Некорректный формат start_time: " . $result['parameters']['start_time'];
            $isValid = false;
        }
    }

    if (isset($result['parameters']['end_time'])) {
        if (!preg_match('/^\d{1,2}:\d{2}$/', $result['parameters']['end_time'])) {
            $errors[] = "Некорректный формат end_time: " . $result['parameters']['end_time'];
            $isValid = false;
        }
    }

    // Проверка статусов
    if (isset($result['parameters']['updates']['status'])) {
        $validStatuses = ['pending', 'in_progress', 'completed'];
        if (!in_array($result['parameters']['updates']['status'], $validStatuses)) {
            $errors[] = "Некорректный статус: " . $result['parameters']['updates']['status'];
            $isValid = false;
        }
    }

    // Проверка приоритетов
    if (isset($result['parameters']['priority'])) {
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        if (!in_array($result['parameters']['priority'], $validPriorities)) {
            $errors[] = "Некорректный приоритет: " . $result['parameters']['priority'];
            $isValid = false;
        }
    }

    // Вывод результата
    if ($isValid) {
        echo $colors['green'] . "✅ Успешно\n" . $colors['reset'];
        $successCount++;
    } else {
        echo $colors['red'] . "❌ Ошибки валидации:\n";
        foreach ($errors as $error) {
            echo "   - " . $error . "\n";
        }
        echo $colors['reset'];
        $failCount++;
    }

    echo $colors['yellow'] . "Результат:\n" . $colors['reset'];
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// Итоги
echo "\n" . $colors['blue'] . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo $colors['cyan'] . "📊 ИТОГИ ТЕСТИРОВАНИЯ:\n" . $colors['reset'];
echo $colors['green'] . "✅ Успешно: " . $successCount . "\n" . $colors['reset'];
echo $colors['red'] . "❌ Провалено: " . $failCount . "\n" . $colors['reset'];
echo "Успешность: " . round($successCount / count($testCommands) * 100, 1) . "%\n";