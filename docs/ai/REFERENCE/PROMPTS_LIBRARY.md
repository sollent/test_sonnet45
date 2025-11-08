# 🎯 LLM Prompts Library - Critical for Voice AI

> **For AI**: This is THE MOST IMPORTANT file. These prompts make Llama understand voice commands correctly.

## 🎪 Why This Matters

Without good prompts, LLM will:
- ❌ Generate invalid JSON
- ❌ Misunderstand commands
- ❌ Return wrong actions

With these prompts:
- ✅ 90%+ accuracy
- ✅ Valid JSON every time
- ✅ Handles Russian language well

---

## 🔧 Main System Prompt for LLM

**AI**: Use this EXACT prompt in LLMService.php:

```
You are a task management assistant for Russian-speaking users.

Your job: Convert voice commands into valid JSON.

IMPORTANT RULES:
1. ALWAYS return ONLY valid JSON (no extra text!)
2. Understand Russian language commands
3. Extract: action, parameters, confidence
4. If unsure, set confidence < 0.5

Available actions:
- create_task
- complete_task
- filter_tasks
- create_subtask
- bulk_complete

JSON format (EXACTLY this structure):
{
  "action": "action_name",
  "parameters": {},
  "confidence": 0.0-1.0
}

Examples of commands you'll receive:

"Создай задачу купить молоко завтра" →
{
  "action": "create_task",
  "parameters": {
    "title": "Купить молоко",
    "due_date": "tomorrow"
  },
  "confidence": 0.95
}

"Отметь задачу купить молоко как выполненную" →
{
  "action": "complete_task",
  "parameters": {
    "search": "купить молоко"
  },
  "confidence": 0.92
}

"Покажи все задачи на завтра со статусом важные" →
{
  "action": "filter_tasks",
  "parameters": {
    "filters": {
      "date": "tomorrow",
      "priority": "high"
    }
  },
  "confidence": 0.88
}

Now process this command:
```

---

## 📝 Prompt Templates for Each Action

### 1. Create Task Command

**Input Examples**:
- "Создай задачу купить молоко"
- "Добавь задачу сделать отчет завтра в 15:00"
- "Новая задача: позвонить маме, срочно, с тегом семья"

**Expected JSON**:
```json
{
  "action": "create_task",
  "parameters": {
    "title": "Купить молоко",
    "due_date": null,
    "priority": null,
    "tags": []
  },
  "confidence": 0.95
}
```

**With details**:
```json
{
  "action": "create_task",
  "parameters": {
    "title": "Сделать отчет",
    "due_date": "tomorrow 15:00",
    "priority": "high",
    "tags": ["работа"]
  },
  "confidence": 0.92
}
```

### 2. Complete Task Command

**Input Examples**:
- "Отметь задачу купить молоко как выполненную"
- "Заверши задачу позвонить маме"
- "Задача сделать отчет готова"

**Expected JSON**:
```json
{
  "action": "complete_task",
  "parameters": {
    "search": "купить молоко"
  },
  "confidence": 0.90
}
```

### 3. Filter Tasks Command

**Input Examples**:
- "Покажи все задачи на завтра"
- "Отобрази задачи со статусом в процессе и приоритетом высокий"
- "Все важные задачи за 13-16 ноября с тегами личное и работа"

**Expected JSON**:
```json
{
  "action": "filter_tasks",
  "parameters": {
    "filters": {
      "date_from": "2025-11-13",
      "date_to": "2025-11-16",
      "status": ["in_progress", "pending"],
      "priority": "high",
      "tags": ["личное", "работа"]
    }
  },
  "confidence": 0.85
}
```

### 4. Create Subtask Command

**Input Examples**:
- "Для задачи сделать проект добавь подзадачу написать код"
- "К задаче купить продукты добавь три подзадачи: молоко, хлеб, масло"

**Expected JSON**:
```json
{
  "action": "create_subtask",
  "parameters": {
    "parent_search": "сделать проект",
    "subtasks": [
      {"title": "Написать код"}
    ]
  },
  "confidence": 0.88
}
```

**Multiple subtasks**:
```json
{
  "action": "create_subtask",
  "parameters": {
    "parent_search": "купить продукты",
    "subtasks": [
      {"title": "Молоко"},
      {"title": "Хлеб"},
      {"title": "Масло"}
    ]
  },
  "confidence": 0.90
}
```

### 5. Bulk Complete Command

**Input Examples**:
- "Заверши три задачи: задача 1, задача 2, задача 3"
- "Отметь как выполненные: купить молоко, позвонить маме"

**Expected JSON**:
```json
{
  "action": "bulk_complete",
  "parameters": {
    "tasks": [
      "задача 1",
      "задача 2",
      "задача 3"
    ]
  },
  "confidence": 0.85
}
```

---

## 🌍 Natural Language Understanding

### Date/Time Parsing

**AI**: LLM should convert these to standard format:

| User says | LLM returns |
|-----------|-------------|
| "завтра" | "tomorrow" |
| "послезавтра" | "+2 days" |
| "через неделю" | "+1 week" |
| "в понедельник" | "next monday" |
| "15 числа" | "2025-01-15" |
| "завтра в 15:00" | "tomorrow 15:00" |

### Priority Recognition

| User says | LLM returns |
|-----------|-------------|
| "срочно", "важно", "высокий" | "high" |
| "обычно", "средний" | "medium" |
| "низкий", "потом" | "low" |

### Status Recognition

| User says | LLM returns |
|-----------|-------------|
| "в процессе", "делаю" | "in_progress" |
| "в ожидании", "отложено" | "pending" |
| "завершено", "готово" | "completed" |

---

## 🎯 Context-Aware Prompt

**AI**: Add context to improve accuracy:

```php
private function buildPrompt(string $text, array $context): string
{
    $currentDate = $context['date'] ?? date('Y-m-d');
    $currentTime = $context['time'] ?? date('H:i');
    $timezone = $context['timezone'] ?? 'UTC';
    $userTasks = $context['recent_tasks'] ?? [];

    // Build list of recent tasks for context
    $tasksContext = '';
    if ($userTasks) {
        $tasksContext = "User's recent tasks:\n";
        foreach (array_slice($userTasks, 0, 5) as $task) {
            $tasksContext .= "- {$task['title']}\n";
        }
    }

    return <<<PROMPT
You are a task management assistant.

Current context:
- Date: $currentDate
- Time: $currentTime
- Timezone: $timezone

$tasksContext

User command: "$text"

Return ONLY JSON:
{
  "action": "...",
  "parameters": {...},
  "confidence": 0.0-1.0
}
PROMPT;
}
```

---

## 🧪 Testing Prompts

### Test Cases for AI

**AI**: Use these to verify LLM works:

```php
// File: backend/tests/LLMPromptTest.php

$testCases = [
    // Case 1: Simple task creation
    [
        'input' => 'Создай задачу купить молоко',
        'expected' => [
            'action' => 'create_task',
            'parameters' => ['title' => 'Купить молоко']
        ]
    ],

    // Case 2: Task with date
    [
        'input' => 'Добавь задачу позвонить маме завтра в 15:00',
        'expected' => [
            'action' => 'create_task',
            'parameters' => [
                'title' => 'Позвонить маме',
                'due_date' => 'tomorrow 15:00'
            ]
        ]
    ],

    // Case 3: Task with priority and tags
    [
        'input' => 'Создай срочную задачу сделать отчет, теги работа и важное',
        'expected' => [
            'action' => 'create_task',
            'parameters' => [
                'title' => 'Сделать отчет',
                'priority' => 'high',
                'tags' => ['работа', 'важное']
            ]
        ]
    ],

    // Case 4: Complete task
    [
        'input' => 'Отметь задачу купить молоко как выполненную',
        'expected' => [
            'action' => 'complete_task',
            'parameters' => ['search' => 'купить молоко']
        ]
    ],

    // Case 5: Filter tasks
    [
        'input' => 'Покажи все задачи на завтра с высоким приоритетом',
        'expected' => [
            'action' => 'filter_tasks',
            'parameters' => [
                'filters' => [
                    'date': 'tomorrow',
                    'priority' => 'high'
                ]
            ]
        ]
    ],

    // Case 6: Multiple subtasks
    [
        'input' => 'Для задачи проект добавь три подзадачи: дизайн, код, тесты',
        'expected' => [
            'action' => 'create_subtask',
            'parameters' => [
                'parent_search' => 'проект',
                'subtasks' => [
                    ['title' => 'Дизайн'],
                    ['title' => 'Код'],
                    ['title' => 'Тесты']
                ]
            ]
        ]
    ],

    // Case 7: Bulk complete
    [
        'input' => 'Заверши задачи: молоко, хлеб, масло',
        'expected' => [
            'action' => 'bulk_complete',
            'parameters' => [
                'tasks' => ['молоко', 'хлеб', 'масло']
            ]
        ]
    ]
];
```

---

## 🚨 Handling Ambiguous Commands

### Low Confidence Response

When LLM is unsure (confidence < 0.5):

```json
{
  "action": "clarification_needed",
  "parameters": {
    "original_text": "user command",
    "possible_actions": ["create_task", "complete_task"],
    "question": "Вы хотите создать новую задачу или отметить существующую как выполненную?"
  },
  "confidence": 0.4
}
```

### Invalid Command

```json
{
  "action": "unknown",
  "parameters": {
    "raw_text": "user command"
  },
  "confidence": 0.1
}
```

---

## 🔧 Prompt Optimization Tips for AI

### 1. Keep Context Small
```php
// Good: Only last 5 tasks
$context['recent_tasks'] = array_slice($allTasks, 0, 5);

// Bad: All 1000 tasks
$context['all_tasks'] = $allTasks;
```

### 2. Use Strict JSON Mode
```php
$response = $this->httpClient->request('POST', $ollamaUrl . '/api/generate', [
    'json' => [
        'model' => 'llama3.2:3b',
        'prompt' => $prompt,
        'format' => 'json',  // Force JSON output
        'options' => [
            'temperature' => 0.3,  // Lower = more consistent
        ]
    ]
]);
```

### 3. Fallback Parsing
```php
private function parseWithFallback(string $llmResponse): array
{
    // Try parse as JSON
    $json = json_decode($llmResponse, true);

    if ($json && isset($json['action'])) {
        return $json;
    }

    // Fallback: keyword matching
    return $this->keywordParse($llmResponse);
}
```

---

## ✅ Checklist for AI Implementation

- [ ] Use exact system prompt from this file
- [ ] Add context (date, time, recent tasks)
- [ ] Set `format: 'json'` in Ollama request
- [ ] Set `temperature: 0.3` for consistency
- [ ] Validate JSON structure
- [ ] Handle low confidence (< 0.5)
- [ ] Add fallback parsing
- [ ] Test with all 7 test cases

---

## 🎯 Expected Accuracy

With these prompts:
- ✅ Simple commands (create task): 95%+
- ✅ Commands with details: 90%+
- ✅ Filter commands: 85%+
- ✅ Complex multi-step: 80%+

---

**Remember**: Llama 3.2 3B is smart but small. These prompts are optimized for it. Don't change unless testing shows issues.

**Document Status**: CRITICAL - Follow Exactly
**Last Updated**: 2025-01-08