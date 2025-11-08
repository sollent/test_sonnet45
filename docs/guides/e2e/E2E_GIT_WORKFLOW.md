# 🔀 Git Workflow для E2E Тестов

> **Руководство по работе с Git при разработке E2E тестов в отдельной ветке**

---

## 📋 Обзор

Мы работаем с E2E тестами в отдельной ветке `feature/e2e-tests`, чтобы:
- ✅ Не мешать работе других нейронок в `main`
- ✅ Создать чистый Pull Request
- ✅ Легко синхронизироваться с изменениями из `main`

---

## 🚀 Начало работы

### 1. Создание ветки (уже сделано)

```bash
# Убедись, что находишься на main и она актуальна
git checkout main
git pull origin main

# Создай новую ветку
git checkout -b feature/e2e-tests

# Запушь ветку на удаленный репозиторий
git push -u origin feature/e2e-tests
```

**Текущее состояние:**
- ✅ Ветка `feature/e2e-tests` создана
- ✅ Мы работаем в этой ветке

---

## ⚠️ ВАЖНО: НЕ переключай ветки!

**Критическое правило:** НИКОГДА не используй `git checkout` для переключения веток, так как это переключит ветку и для других нейронок, работающих параллельно в этом же проекте!

**Вместо этого:** Всегда работай в текущей ветке `feature/e2e-tests` и синхронизируйся с `main` БЕЗ переключения.

---

## 🔄 Workflow синхронизации с main

### Вариант 1: Rebase (рекомендуется)

**Когда использовать:** Когда хочешь чистую историю коммитов

```bash
# 1. Убедись, что ты на правильной ветке (НЕ переключайся!)
git branch --show-current  # должно быть: feature/e2e-tests

# 2. Сохрани свои изменения (если есть незакоммиченные)
git add .
git commit -m "WIP: текущая работа"

# 3. Получи последние изменения из main (БЕЗ переключения!)
git fetch origin main

# 4. Примени изменения из main поверх твоих коммитов (БЕЗ переключения!)
git rebase origin/main

# 5. Если есть конфликты - разреши их, затем:
git add .
git rebase --continue

# 6. Запушь обновленную ветку (force push нужен после rebase)
git push --force-with-lease origin feature/e2e-tests
```

### Вариант 2: Merge (проще, но менее чистая история)

**Когда использовать:** Когда не хочешь возиться с rebase

```bash
# 1. Убедись, что ты на правильной ветке
git branch --show-current  # должно быть: feature/e2e-tests

# 2. Сохрани свои изменения
git add .
git commit -m "WIP: текущая работа"

# 3. Получи последние изменения из main (БЕЗ переключения!)
git fetch origin main

# 4. Влей изменения из main в текущую ветку (БЕЗ переключения!)
git merge origin/main

# 5. Разреши конфликты (если есть)
# 6. Запушь изменения
git push origin feature/e2e-tests
```

---

## 📝 Ежедневный workflow

### Когда начинаешь работу:

```bash
# 1. Проверь текущую ветку (НЕ переключайся!)
git branch --show-current
# Должно быть: feature/e2e-tests
# Если нет - значит кто-то другой переключил, но ты НЕ должен переключать обратно!

# 2. Обнови ветку с main (если нужно) - БЕЗ переключения!
git fetch origin main
git rebase origin/main  # или git merge origin/main
```

### Когда делаешь коммиты:

```bash
# 1. Проверь статус
git status

# 2. Добавь изменения
git add <файлы>

# 3. Сделай коммит с понятным сообщением
git commit -m "feat(e2e): add registration tests

- Implement TC-AUTH-001: successful registration
- Add RegisterPage page object
- Add test fixtures for auth"

# 4. Запушь изменения
git push origin feature/e2e-tests
```

**Важно:** Все коммиты E2E тестов должны быть в ветке `feature/e2e-tests`, НЕ в `main`!

---

## 🔀 Синхронизация с main (когда main обновляется)

Если другая нейронка (Claude Code) делает изменения в `main`, тебе нужно синхронизироваться:

### Шаг 1: Проверь, что изменилось в main

```bash
# Убедись, что на правильной ветке (НЕ переключайся!)
git branch --show-current

# Посмотри последние коммиты в main
git log HEAD..origin/main --oneline
```

### Шаг 2: Синхронизируйся (БЕЗ переключения веток!)

```bash
# Вариант A: Rebase (чистая история)
git fetch origin main
git rebase origin/main

# Вариант B: Merge (проще)
git fetch origin main
git merge origin/main
```

### Шаг 3: Разреши конфликты (если есть)

```bash
# Git покажет конфликтующие файлы
# Отредактируй их, затем:
git add <разрешенные_файлы>
git rebase --continue  # или просто git commit (если merge)
```

---

## 🎯 Создание Pull Request

Когда E2E тесты готовы (или часть готова):

### 1. Убедись, что ветка актуальна

```bash
# Проверь текущую ветку (НЕ переключайся!)
git branch --show-current

# Синхронизируйся с main (БЕЗ переключения!)
git fetch origin main
git rebase origin/main  # или merge
git push --force-with-lease origin feature/e2e-tests
```

### 2. Создай Pull Request

1. Иди на GitHub/GitLab
2. Нажми "New Pull Request"
3. Base: `main` ← Compare: `feature/e2e-tests`
4. Напиши описание:
   ```
   ## E2E Tests Implementation
   
   This PR adds E2E tests for authentication flow (registration).
   
   ### What's included:
   - ✅ Registration tests (7 test cases)
   - ✅ Page Object Model structure
   - ✅ Test fixtures and helpers
   - ✅ Playwright configuration
   
   ### Test coverage:
   - TC-AUTH-001: Successful registration
   - TC-AUTH-002: Empty fields validation
   - TC-AUTH-003: Invalid email validation
   - TC-AUTH-004: Password mismatch validation
   - TC-AUTH-005: Weak password validation
   - TC-AUTH-006: Duplicate email
   - TC-AUTH-007: Google OAuth button
   
   ### Next steps:
   - [ ] Login tests
   - [ ] Task creation tests
   - [ ] Task editing tests
   ```

### 3. После мерджа PR

**Важно:** После мерджа PR в `main`, другая нейронка может переключиться на `main`. Ты НЕ должен переключаться сам!

Если нужно продолжить работу:
- Подожди, пока другая нейронка переключится на `main` (если нужно)
- Или продолжай работать в `feature/e2e-tests` (если ветка еще существует)
- Или создай новую ветку (но это должен сделать пользователь, не ты!)

---

## ⚠️ Важные правила

### ✅ ДЕЛАЙ:

1. **Всегда работай в `feature/e2e-tests`** - НЕ коммить в `main`
2. **Проверяй текущую ветку** перед началом: `git branch --show-current`
3. **Регулярно синхронизируйся** с `main` через `git fetch` + `rebase/merge` (БЕЗ переключения!)
4. **Используй понятные коммиты**: `feat(e2e): ...`, `test(e2e): ...`
5. **Делай маленькие коммиты** - по одному тесту или фиче
6. **Проверяй статус** перед началом работы: `git status`

### ❌ НЕ ДЕЛАЙ:

1. **НЕ используй `git checkout`** для переключения веток - это переключит ветку и для других нейронок!
2. **НЕ коммить в `main`** - только в `feature/e2e-tests`
3. **НЕ делай force push в `main`** - только в свою ветку
4. **НЕ игнорируй конфликты** - разрешай их сразу
5. **НЕ забывай синхронизироваться** - иначе будет много конфликтов
6. **НЕ переключайся на другие ветки** - работай только в текущей!

---

## 🔧 Полезные команды

### Проверка статуса

```bash
# Какая ветка активна?
git branch

# Что изменилось?
git status

# Какие коммиты есть в моей ветке, но нет в main?
git log main..feature/e2e-tests --oneline

# Какие файлы отличаются от main?
git diff main...feature/e2e-tests --name-only
```

### Отмена изменений

```bash
# Отменить незакоммиченные изменения
git restore <файл>

# Отменить все незакоммиченные изменения
git restore .

# Отменить последний коммит (но оставить изменения)
git reset --soft HEAD~1
```

### Просмотр истории

```bash
# Красивая история с графом
git log --oneline --graph --all -20

# История только моей ветки
git log feature/e2e-tests --oneline -10
```

---

## 📊 Текущее состояние

**Активная ветка:** `feature/e2e-tests`  
**Базовая ветка:** `main`  
**Статус:** Готово к началу разработки E2E тестов

---

## 🎯 Чеклист перед началом работы

- [ ] Проверь текущую ветку: `git branch --show-current` (должно быть `feature/e2e-tests`)
- [ ] **НЕ переключайся** на другую ветку, даже если текущая не `feature/e2e-tests`!
- [ ] Синхронизируйся с `main` через `git fetch origin main` + `git rebase origin/main` (если нужно)
- [ ] Проверь `git status` - нет ли незакоммиченных изменений
- [ ] Готов начать разработку!

## 🚨 КРИТИЧЕСКОЕ ПРАВИЛО

**НИКОГДА не используй `git checkout` для переключения веток!**

Это переключит ветку и для других нейронок, работающих параллельно. Вместо этого:
- Проверяй текущую ветку: `git branch --show-current`
- Работай в текущей ветке
- Синхронизируйся через `git fetch` + `rebase/merge` БЕЗ переключения

---

**Последнее обновление:** 2025-01-05  
**Ветка:** `feature/e2e-tests`

