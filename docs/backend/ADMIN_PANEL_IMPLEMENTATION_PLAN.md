# 🎛️ Панель EasyAdmin - План корпоративной реализации

> **Версия**: 1.0
> **Дата**: 2025-11-10
> **Статус**: Готово к реализации
> **Оценка времени**: 12-15 часов (соло разработка + помощь ИИ)

---

## 📋 Краткое резюме

Этот документ описывает **полную реализацию корпоративной админ-панели** для системы управления задачами с использованием EasyAdmin 4.18. Админ-панель предоставит комплексные CRUD-операции, расширенную фильтрацию, мониторинг системы, отслеживание активности пользователей и возможности технической поддержки.

### Текущее состояние
- ✅ Базовая админ-панель с User CRUD (полностью функциональна)
- ✅ Система аутентификации с требованием ROLE_ADMIN
- ✅ Современная кастомная страница входа
- ✅ Безопасность настроена правильно

### Целевое состояние
**Полнофункциональная админ-панель** с:
- 🎯 **8 CRUD-контроллерами сущностей** (User, Task, Tag, RecurrenceRule, MediaObject, TaskAttachment, RefreshToken, плюс AuditLog)
- 📊 **Дашбордом с системными метриками** (пользователи, задачи, хранилище, активность)
- 🔍 **Расширенным поиском и фильтрацией** (по всем сущностям)
- 📈 **Мониторингом активности пользователей** (аудит всех действий)
- 🛠️ **Инструментами технической поддержки** (массовые операции, экспорт данных, имперсонация пользователей)
- 🔐 **Системой прав доступа** (ROLE_ADMIN, ROLE_SUPER_ADMIN)
- 📤 **Функциональностью экспорта** (экспорт CSV, Excel для всех сущностей)

---

## 📑 Содержание

### Быстрая навигация

#### 🎯 Планирование и проектирование
- [Цели и требования](#-цели-и-требования)
- [Архитектурный дизайн](#-архитектурный-дизайн)
- [Сценарии использования (сценарии технической поддержки)](#сценарии-использования-сценарии-технической-поддержки)

#### 📊 Фазы реализации

- **[ФАЗА 1: Критические CRUD-контроллеры](#фаза-1-критические-crud-контроллеры-6-8-часов)** (6-8 часов)
  - [Шаг 1: TaskCrudController](#шаг-1-taskcrudcontroller-3-часа) - Основная функциональность (3ч)
  - [Шаг 2: TagCrudController](#шаг-2-tagcrudcontroller-1-час) - Категоризация пользователей (1ч)
  - [Шаг 3: TaskAttachmentCrudController](#шаг-3-taskattachmentcrudcontroller-2-часа) - Управление файлами (2ч)
  - [Шаг 4: RecurrenceRuleCrudController](#шаг-4-recurrencerulecrudcontroller-2-часа) - Повторяющиеся задачи (2ч)

- **[ФАЗА 2: Поддерживающие сущности](#фаза-2-поддерживающие-сущности-3-4-часа)** ✅ **ЗАВЕРШЕНО** (3-4 часа)
  - [Шаг 5: MediaObjectCrudController](#шаг-5-mediaobjectcrudcontroller-15-часа) - Медиа-библиотека (1.5ч) ✅
  - [Шаг 6: RefreshTokenCrudController](#шаг-6-refreshtokencrudcontroller-05-часа) - Управление сессиями (0.5ч) ✅
  - [Шаг 7: AuditLogCrudController](#шаг-7-auditlogcrudcontroller-2-часа-новая-сущность) - Отслеживание активности (2ч) ✅

- **[ФАЗА 3: Дашборд и улучшения](#фаза-3-дашборд-и-улучшения-3-4-часа)** ✅ **ЗАВЕРШЕНО** (3-4 часа)
  - [Шаг 8: Расширенный дашборд](#шаг-8-расширенный-дашборд-2-часа) - Обзор системы (2ч) ✅
  - [Шаг 9: Конфигурация меню](#шаг-9-конфигурация-меню-05-часа) - Структура навигации (0.5ч) ✅
  - [Шаг 10: Массовые действия и экспорт](#шаг-10-массовые-действия-и-экспорт-1-час) - Пакетные операции (1ч) ✅

#### 🔐 Дополнительные разделы
- [Система прав доступа (ROLE_ADMIN vs ROLE_SUPER_ADMIN)](#-система-прав-доступа-role_admin-vs-role_super_admin)
- [Чек-лист реализации](#-чек-лист-реализации)
- [Ожидаемые результаты](#-ожидаемые-результаты)
- [Технические заметки по реализации](#-технические-заметки-по-реализации)
- [Чек-лист запуска](#-чек-лист-запуска)
- [Поддержка и устранение неполадок](#-поддержка-и-устранение-неполадок)

---

## 🎯 Цели и требования

### Бизнес-цели
1. **Эффективность команды поддержки**: Обеспечить быстрое решение проблем пользователей без доступа к базе данных
2. **Видимость данных**: Мониторить всю активность приложения и действия пользователей в реальном времени
3. **Модерация контента**: Возможность управлять и модерировать пользовательский контент
4. **Здоровье системы**: Отслеживать системные метрики и выявлять проблемы на ранней стадии
5. **Управление пользователями**: Полное управление жизненным циклом пользователей (активация, блокировка, удаление)

### Технические требования
- **Принципы SOLID**: Следовать существующим паттернам кодовой базы
- **Типобезопасность**: Полная типизация PHP 8.3 везде
- **Безопасность**: Доступ на основе ролей с детальными правами
- **Производительность**: Оптимизированные запросы с правильными индексами
- **Поддерживаемость**: Четкое разделение ответственности, принцип DRY
- **Документация**: Комплексная встроенная документация

### Сценарии использования (сценарии технической поддержки)

#### Сценарий 1: Пользователь сообщает об ошибке с тегами
**Проблема**: "Помогите! Я не могу удалить 2 тега из задачи 'Купить молоко'"

**Рабочий процесс администратора**:
1. Поиск пользователя по email в UserCrudController
2. Клик на пользователя → Просмотр его задач (связанное поле)
3. Поиск задачи "Купить молоко" (поиск по названию)
4. Открыть детали задачи → Просмотр связанных тегов
5. Удалить проблемные теги или удалить/переназначить их
6. **Время решения**: < 2 минут

#### Сценарий 2: Пользователь потерял доступ к задачам
**Проблема**: "Все мои задачи исчезли после входа"

**Рабочий процесс администратора**:
1. Найти пользователя в UserCrudController
2. Просмотреть задачи пользователя (связь OneToMany)
3. Проверить, заархивированы ли задачи (`isArchived = true`)
4. Массово разархивировать задачи используя пакетное действие
5. Проверить во фронтенде
6. **Время решения**: < 1 минуты

#### Сценарий 3: Повторяющаяся задача не создается
**Проблема**: "Моя ежедневная задача перестала создаваться автоматически"

**Рабочий процесс администратора**:
1. Найти RecurrenceRule пользователя в RecurrenceRuleCrudController
2. Проверить статус `isActive` (может быть деактивировано)
3. Просмотреть `currentOccurrences` vs `maxOccurrences` (может быть достигнуто)
4. Проверить `nextOccurrenceDate` (может быть в прошлом из-за сбоя cron)
5. Вручную обновить правило или запустить команду recurrence
6. **Время решения**: < 3 минут

#### Сценарий 4: Анализ производительности системы
**Рабочий процесс администратора**:
1. Перейти на дашборд
2. Просмотреть системные метрики (количество задач, пользователей, хранилище медиа)
3. Проверить AuditLog на подозрительную активность (массовые удаления, ошибки)
4. Определить активных пользователей (TaskCrudController → фильтр по пользователю → подсчет)
5. Оптимизировать или связаться с пользователем
6. **Время решения**: < 5 минут

---

## 🏗️ Архитектурный дизайн

### Иерархия сущностей (по приоритету)

#### **Фаза 1: Критические сущности** (6-8 часов)
1. **TaskCrudController** - Основная функция (30% работы)
2. **TagCrudController** - Категоризация пользователей (10% работы)
3. **TaskAttachmentCrudController** - Управление файлами (15% работы)
4. **RecurrenceRuleCrudController** - Повторяющиеся задачи (15% работы)

#### **Фаза 2: Поддерживающие сущности** (3-4 часа)
5. **MediaObjectCrudController** - Медиа-библиотека (10% работы)
6. **RefreshTokenCrudController** - Управление сессиями (5% работы)
7. **AuditLogCrudController** - Отслеживание активности (НОВОЕ - 15% работы)

#### **Фаза 3: Дашборд и улучшения** (3-4 часа)
8. **Dashboard Metrics** - Обзор системы (10% работы)
9. **Bulk Actions** - Пакетные операции (5% работы)
10. **Export Functionality** - Экспорт данных (5% работы)

### Карта связей сущностей для админки

```
DashboardController
├── UserCrudController [СУЩЕСТВУЮЩИЙ ✅]
│   ├── 1:N → TaskCrudController [НОВЫЙ]
│   ├── 1:N → TagCrudController [НОВЫЙ]
│   ├── 1:N → MediaObjectCrudController [НОВЫЙ]
│   ├── 1:N → RecurrenceRuleCrudController [НОВЫЙ]
│   └── 1:N → AuditLogCrudController [НОВЫЙ]
│
├── TaskCrudController [НОВЫЙ]
│   ├── N:1 → UserCrudController (владелец)
│   ├── 1:N → TaskCrudController (подзадачи - самоссылка)
│   ├── M:N → TagCrudController (теги)
│   ├── 1:N → TaskAttachmentCrudController (вложения)
│   ├── 1:1 → RecurrenceRuleCrudController (шаблон)
│   └── N:1 → RecurrenceRuleCrudController (создано_из)
│
├── TagCrudController [НОВЫЙ]
│   ├── N:1 → UserCrudController (владелец)
│   └── M:N → TaskCrudController (задачи)
│
├── RecurrenceRuleCrudController [НОВЫЙ]
│   ├── N:1 → UserCrudController (создано)
│   └── 1:1 → TaskCrudController (шаблонная_задача)
│
├── TaskAttachmentCrudController [НОВЫЙ]
│   ├── N:1 → TaskCrudController (задача)
│   └── N:1 → UserCrudController (загрузил)
│
├── MediaObjectCrudController [НОВЫЙ]
│   └── N:1 → UserCrudController (загрузил)
│
├── RefreshTokenCrudController [НОВЫЙ]
│   └── N:1 → UserCrudController (через username)
│
└── AuditLogCrudController [НОВЫЙ]
    ├── N:1 → UserCrudController (пользователь, выполнивший действие)
    └── Polymorphic → Любая сущность (entity_type + entity_id)
```

---

## 📊 План реализации - пошагово

### **ФАЗА 1: Критические CRUD-контроллеры** (6-8 часов)

#### **Шаг 1: TaskCrudController** (3 часа)

**Сущность**: `App\Entity\Task`

**Сложность**: **ВЫСОКАЯ** (самая сложная из-за вложенных связей)

##### Конфигурация полей

| Поле | Тип | Видимо на | Сортируемо | Поиск | Заметки |
|-------|------|------------|----------|------------|-------|
| `id` | IdField | Index | ✅ | ❌ | Автогенерируемое |
| `user` | AssociationField | All | ✅ | ✅ | Обязательное, автозаполнение |
| `parentTask` | AssociationField | All | ✅ | ✅ | Nullable, самоссылка |
| `title` | TextField | All | ✅ | ✅ | Обязательное, макс 255 |
| `description` | TextareaField | Detail/Form | ❌ | ✅ | Nullable, макс 5000 |
| `status` | ChoiceField | All | ✅ | ✅ | Enum: PENDING/IN_PROGRESS/COMPLETED/CANCELLED |
| `priority` | ChoiceField | All | ✅ | ✅ | Enum: LOW/MEDIUM/HIGH/URGENT |
| `startDate` | DateTimeField | All | ✅ | ❌ | Nullable |
| `dueDate` | DateTimeField | All | ✅ | ❌ | Nullable |
| `completedAt` | DateTimeField | Detail | ✅ | ❌ | Автоустановка, nullable |
| `sortOrder` | IntegerField | Detail | ✅ | ❌ | По умолчанию 0 |
| `isArchived` | BooleanField | All | ✅ | ✅ | По умолчанию false |
| `isRecurringTemplate` | BooleanField | Detail | ✅ | ✅ | По умолчанию false |
| `tags` | AssociationField | All | ❌ | ❌ | M:N, автозаполнение |
| `subtasks` | CollectionField | Detail | ❌ | ❌ | 1:N, только чтение |
| `attachments` | CollectionField | Detail | ❌ | ❌ | 1:N, только чтение |
| `recurrenceRule` | AssociationField | Detail | ❌ | ❌ | 1:1, nullable |
| `generatedFromRule` | AssociationField | Detail | ❌ | ❌ | N:1, nullable |
| `createdAt` | DateTimeField | Detail | ✅ | ❌ | Авто |
| `updatedAt` | DateTimeField | Detail | ✅ | ❌ | Авто |

##### Фильтры
- **User** (EntityFilter): Фильтр по владельцу
- **Status** (ChoiceFilter): Множественный выбор
- **Priority** (ChoiceFilter): Множественный выбор
- **Is Archived** (BooleanFilter): True/False/All
- **Is Recurring Template** (BooleanFilter): True/False/All
- **Tags** (EntityFilter): Фильтр по связанным тегам
- **Due Date Range** (DateTimeFilter): От/До
- **Created At Range** (DateTimeFilter): От/До
- **Has Subtasks** (BooleanFilter): Задачи с/без подзадач
- **Parent Task** (EntityFilter): Фильтр по родительской задаче

##### Действия
- **NEW**: Создать задачу (иконка: plus)
- **EDIT**: Редактировать задачу (иконка: edit)
- **DELETE**: Удалить задачу и все подзадачи (иконка: trash, требуется подтверждение)
- **DETAIL**: Просмотр полных деталей (иконка: eye)
- **BATCH_COMPLETE**: Отметить несколько задач как завершенные (пакетное действие)
- **BATCH_ARCHIVE**: Архивировать несколько задач (пакетное действие)
- **BATCH_DELETE**: Удалить несколько задач (пакетное действие, требуется подтверждение)
- **EXPORT**: Экспортировать выбранные задачи в CSV/Excel (пакетное действие)

##### Хуки бизнес-логики

```php
public function configureActions(Actions $actions): Actions
{
    $completeAction = Action::new('complete', 'Complete')
        ->linkToCrudAction('completeTask')
        ->displayIf(static fn (Task $task) => !$task->isCompleted())
        ->setIcon('fa fa-check');

    $archiveAction = Action::new('archive', 'Archive')
        ->linkToCrudAction('archiveTask')
        ->displayIf(static fn (Task $task) => !$task->isArchived())
        ->setIcon('fa fa-archive');

    return $actions
        ->add(Crud::PAGE_INDEX, $completeAction)
        ->add(Crud::PAGE_INDEX, $archiveAction)
        ->add(Crud::PAGE_DETAIL, $completeAction)
        ->add(Crud::PAGE_DETAIL, $archiveAction);
}

public function completeTask(AdminContext $context): Response
{
    $task = $context->getEntity()->getInstance();
    $task->setStatus(TaskStatus::COMPLETED);
    $task->setCompletedAt(new \DateTimeImmutable());

    $this->entityManager->flush();

    $this->addFlash('success', "Задача '{$task->getTitle()}' завершена!");

    return $this->redirect($context->getReferrer());
}

public function archiveTask(AdminContext $context): Response
{
    $task = $context->getEntity()->getInstance();
    $task->setIsArchived(true);

    $this->entityManager->flush();

    $this->addFlash('success', "Задача '{$task->getTitle()}' заархивирована!");

    return $this->redirect($context->getReferrer());
}

public function createIndexQueryBuilder(/* ... */): QueryBuilder
{
    return parent::createIndexQueryBuilder(/* ... */)
        ->leftJoin('entity.user', 'u')
        ->addSelect('u')
        ->leftJoin('entity.tags', 't')
        ->addSelect('t')
        ->leftJoin('entity.subtasks', 's')
        ->addSelect('s')
        // Жадная загрузка для предотвращения N+1 запросов
        ->orderBy('entity.createdAt', 'DESC');
}
```

##### Кастомная валидация форм

```php
public function persistEntity(EntityManagerInterface $em, $entityInstance): void
{
    /** @var Task $task */
    $task = $entityInstance;

    // Валидация: startDate < dueDate
    if ($task->getStartDate() && $task->getDueDate()) {
        if ($task->getStartDate() > $task->getDueDate()) {
            $this->addFlash('error', 'Дата начала должна быть раньше срока выполнения!');
            throw new \RuntimeException('Invalid dates');
        }
    }

    // Валидация: Родительская задача не может быть самой собой
    if ($task->getParentTask() && $task->getParentTask()->getId() === $task->getId()) {
        $this->addFlash('error', 'Задача не может быть родителем самой себя!');
        throw new \RuntimeException('Invalid parent');
    }

    parent::persistEntity($em, $entityInstance);
}
```

##### Оптимизация отображения

```php
public function configureFields(string $pageName): iterable
{
    // Оптимизация отображения полей в зависимости от страницы
    $id = IdField::new('id');
    $user = AssociationField::new('user')
        ->autocomplete()
        ->setCrudController(UserCrudController::class)
        ->formatValue(fn ($value, Task $task) => $task->getUser()->getEmail());

    $title = TextField::new('title')
        ->setMaxLength(50); // Обрезать на index

    $status = ChoiceField::new('status')
        ->setChoices([
            'Ожидает' => TaskStatus::PENDING,
            'В работе' => TaskStatus::IN_PROGRESS,
            'Завершена' => TaskStatus::COMPLETED,
            'Отменена' => TaskStatus::CANCELLED,
        ])
        ->renderAsBadges([
            TaskStatus::PENDING->value => 'secondary',
            TaskStatus::IN_PROGRESS->value => 'primary',
            TaskStatus::COMPLETED->value => 'success',
            TaskStatus::CANCELLED->value => 'danger',
        ]);

    $priority = ChoiceField::new('priority')
        ->setChoices([
            'Низкий' => TaskPriority::LOW,
            'Средний' => TaskPriority::MEDIUM,
            'Высокий' => TaskPriority::HIGH,
            'Срочный' => TaskPriority::URGENT,
        ])
        ->renderAsBadges([
            TaskPriority::LOW->value => 'secondary',
            TaskPriority::MEDIUM->value => 'info',
            TaskPriority::HIGH->value => 'warning',
            TaskPriority::URGENT->value => 'danger',
        ]);

    $dueDate = DateTimeField::new('dueDate')
        ->setFormat('dd.MM.yyyy HH:mm')
        ->setTimezone('Europe/Moscow');

    $isArchived = BooleanField::new('isArchived')
        ->renderAsSwitch(false);

    $tags = AssociationField::new('tags')
        ->autocomplete()
        ->formatValue(function ($value, Task $task) {
            return implode(', ', array_map(
                fn ($tag) => $tag->getName(),
                $task->getTags()->toArray()
            ));
        });

    $subtaskCount = IntegerField::new('subtaskCount', 'Subtasks')
        ->formatValue(fn ($value, Task $task) => $task->getSubtasks()->count())
        ->onlyOnIndex();

    $createdAt = DateTimeField::new('createdAt')
        ->setFormat('dd.MM.yyyy HH:mm')
        ->onlyOnDetail();

    // Возврат полей в зависимости от страницы
    if (Crud::PAGE_INDEX === $pageName) {
        return [$id, $user, $title, $status, $priority, $dueDate, $isArchived, $tags, $subtaskCount];
    }

    if (Crud::PAGE_DETAIL === $pageName) {
        return [/* все поля со связями */];
    }

    return [/* поля формы */];
}
```

---

#### **Шаг 2: TagCrudController** (1 час)

**Сущность**: `App\Entity\Tag`

**Сложность**: **НИЗКАЯ** (простая сущность с базовой связью M:N)

##### Конфигурация полей

| Поле | Тип | Видимо на | Сортируемо | Поиск | Заметки |
|-------|------|------------|----------|------------|-------|
| `id` | IdField | Index | ✅ | ❌ | Автогенерируемое |
| `user` | AssociationField | All | ✅ | ✅ | Обязательное, автозаполнение |
| `name` | TextField | All | ✅ | ✅ | Обязательное, макс 50, уникальное на пользователя |
| `color` | ColorField | All | ✅ | ❌ | Hex цвет (#RRGGBB), по умолчанию #3B82F6 |
| `icon` | TextField | All | ❌ | ✅ | Nullable, имя иконки |
| `usageCount` | IntegerField | All | ✅ | ❌ | Счетчик, только чтение |
| `tasks` | AssociationField | Detail | ❌ | ❌ | M:N, коллекция только для чтения |
| `createdAt` | DateTimeField | Detail | ✅ | ❌ | Авто |
| `updatedAt` | DateTimeField | Detail | ✅ | ❌ | Авто |

##### Фильтры
- **User** (EntityFilter): Фильтр по владельцу
- **Name** (TextFilter): Частичное совпадение
- **Usage Count Range** (NumberFilter): Мин/Макс
- **Created At Range** (DateTimeFilter): От/До
- **Has Tasks** (BooleanFilter): Теги со/без связанных задач

##### Действия
- **NEW**: Создать тег (иконка: plus, палитра цветов)
- **EDIT**: Редактировать тег (иконка: edit)
- **DELETE**: Удалить тег (иконка: trash, подтверждение с предупреждением о количестве задач)
- **DETAIL**: Просмотр полных деталей включая список задач (иконка: eye)
- **MERGE**: Объединить несколько тегов в один (пакетное действие, кастомная модалка)
- **BATCH_DELETE**: Удалить несколько тегов (пакетное действие, требуется подтверждение)
- **EXPORT**: Экспорт тегов со статистикой использования в CSV (пакетное действие)

##### Хуки бизнес-логики

```php
public function persistEntity(EntityManagerInterface $em, $entityInstance): void
{
    /** @var Tag $tag */
    $tag = $entityInstance;

    // Валидация: Имя + Пользователь должны быть уникальными
    $existingTag = $em->getRepository(Tag::class)->findOneBy([
        'name' => $tag->getName(),
        'user' => $tag->getUser(),
    ]);

    if ($existingTag && $existingTag->getId() !== $tag->getId()) {
        $this->addFlash('error', "Тег '{$tag->getName()}' уже существует для этого пользователя!");
        throw new \RuntimeException('Duplicate tag name');
    }

    // Валидация: Формат цвета
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $tag->getColor())) {
        $this->addFlash('error', 'Неверный формат цвета! Используйте hex формат (#RRGGBB)');
        throw new \RuntimeException('Invalid color');
    }

    parent::persistEntity($em, $entityInstance);
}

public function deleteEntity(EntityManagerInterface $em, $entityInstance): void
{
    /** @var Tag $tag */
    $tag = $entityInstance;

    $taskCount = $tag->getTasks()->count();

    if ($taskCount > 0) {
        $this->addFlash('warning', "Тег '{$tag->getName()}' удален из {$taskCount} задач.");
    }

    parent::deleteEntity($em, $entityInstance);
}
```

##### Кастомное действие: Объединение тегов

```php
public function configureActions(Actions $actions): Actions
{
    $mergeAction = Action::new('mergeTags', 'Объединить теги')
        ->linkToCrudAction('mergeTags')
        ->addCssClass('btn btn-warning')
        ->setIcon('fa fa-compress-alt')
        ->displayAsButton();

    return $actions
        ->addBatchAction($mergeAction);
}

public function mergeTags(BatchActionDto $batchActionDto): Response
{
    $tagIds = $batchActionDto->getEntityIds();

    if (count($tagIds) < 2) {
        $this->addFlash('error', 'Выберите минимум 2 тега для объединения!');
        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    // Отрисовка кастомной формы объединения
    $form = $this->createForm(TagMergeType::class, [
        'source_tags' => $tagIds,
    ]);

    $form->handleRequest($this->requestStack->getCurrentRequest());

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        $targetTagId = $data['target_tag'];

        $targetTag = $this->entityManager->find(Tag::class, $targetTagId);
        $sourceTags = $this->entityManager->getRepository(Tag::class)->findBy(['id' => $tagIds]);

        // Перенос всех задач из исходных тегов в целевой тег
        foreach ($sourceTags as $sourceTag) {
            if ($sourceTag->getId() === $targetTag->getId()) {
                continue;
            }

            foreach ($sourceTag->getTasks() as $task) {
                if (!$task->getTags()->contains($targetTag)) {
                    $task->addTag($targetTag);
                }
                $task->removeTag($sourceTag);
            }

            $this->entityManager->remove($sourceTag);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Теги успешно объединены!');

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    return $this->render('admin/tag/merge.html.twig', [
        'form' => $form->createView(),
        'tags' => $this->entityManager->getRepository(Tag::class)->findBy(['id' => $tagIds]),
    ]);
}
```

---

#### **Шаг 3: TaskAttachmentCrudController** (2 часа)

**Сущность**: `App\Entity\TaskAttachment`

**Сложность**: **СРЕДНЯЯ** (обработка файлов, управление хранилищем)

##### Конфигурация полей

| Поле | Тип | Видимо на | Сортируемо | Поиск | Заметки |
|-------|------|------------|----------|------------|-------|
| `id` | IdField | Index | ✅ | ❌ | Автогенерируемое |
| `task` | AssociationField | All | ✅ | ✅ | Обязательное |
| `uploadedBy` | AssociationField | All | ✅ | ✅ | Обязательное |
| `fileName` | TextField | All | ✅ | ✅ | Сгенерированное имя файла |
| `originalName` | TextField | All | ✅ | ✅ | Исходное имя файла |
| `mimeType` | TextField | All | ✅ | ✅ | MIME-тип файла |
| `fileType` | ChoiceField | All | ✅ | ✅ | image/document/video/other |
| `fileSize` | IntegerField | All | ✅ | ❌ | В байтах, форматированный вывод |
| `filePath` | TextField | Detail | ❌ | ❌ | Путь хранения, только чтение |
| `uploadedAt` | DateTimeField | All | ✅ | ❌ | Авто |

##### Фильтры
- **Task** (EntityFilter): Фильтр по родительской задаче
- **Uploaded By** (EntityFilter): Фильтр по загрузившему
- **File Type** (ChoiceFilter): image/document/video/other
- **MIME Type** (TextFilter): Частичное совпадение
- **File Size Range** (NumberFilter): Мин/Макс (МБ)
- **Uploaded At Range** (DateTimeFilter): От/До

##### Действия
- **NEW**: Загрузить вложение (иконка: upload)
- **DETAIL**: Просмотр деталей с предпросмотром (иконка: eye)
- **DELETE**: Удалить вложение и файл из хранилища (иконка: trash, подтверждение)
- **DOWNLOAD**: Скачать файл (кнопка действия)
- **BATCH_DELETE**: Удалить несколько вложений (пакетное действие, подтверждение)
- **EXPORT**: Экспорт метаданных вложений в CSV (пакетное действие)

##### Кастомное отображение

```php
public function configureFields(string $pageName): iterable
{
    $id = IdField::new('id');

    $task = AssociationField::new('task')
        ->autocomplete()
        ->setCrudController(TaskCrudController::class)
        ->formatValue(fn ($value, TaskAttachment $attachment) =>
            $attachment->getTask()->getTitle()
        );

    $uploadedBy = AssociationField::new('uploadedBy')
        ->autocomplete()
        ->setCrudController(UserCrudController::class)
        ->formatValue(fn ($value, TaskAttachment $attachment) =>
            $attachment->getUploadedBy()->getEmail()
        );

    $fileName = TextField::new('fileName')
        ->setMaxLength(40);

    $originalName = TextField::new('originalName')
        ->setMaxLength(40);

    $fileSize = IntegerField::new('fileSize')
        ->formatValue(fn ($value, TaskAttachment $attachment) =>
            $attachment->getHumanReadableSize()
        );

    $fileType = ChoiceField::new('fileType')
        ->setChoices([
            'Изображение' => 'image',
            'Документ' => 'document',
            'Видео' => 'video',
            'Прочее' => 'other',
        ])
        ->renderAsBadges([
            'image' => 'success',
            'document' => 'primary',
            'video' => 'warning',
            'other' => 'secondary',
        ]);

    $preview = ImageField::new('filePath', 'Предпросмотр')
        ->setBasePath('/uploads/tasks/')
        ->onlyWhen('image' === $pageData['fileType'] ?? null)
        ->onlyOnDetail();

    $downloadLink = Field::new('download', 'Скачать')
        ->formatValue(function ($value, TaskAttachment $attachment) {
            return sprintf(
                '<a href="/uploads/tasks/%s" download="%s" class="btn btn-sm btn-primary">
                    <i class="fa fa-download"></i> Скачать
                </a>',
                $attachment->getFileName(),
                $attachment->getOriginalName()
            );
        })
        ->onlyOnDetail();

    if (Crud::PAGE_INDEX === $pageName) {
        return [$id, $task, $uploadedBy, $originalName, $fileType, $fileSize, $uploadedAt];
    }

    return [/* все поля */];
}
```

##### Обработка загрузки файлов

```php
public function persistEntity(EntityManagerInterface $em, $entityInstance): void
{
    /** @var TaskAttachment $attachment */
    $attachment = $entityInstance;

    // Обработка загрузки файла (предполагается UploadedFile в форме)
    $uploadedFile = $this->requestStack->getCurrentRequest()->files->get('file');

    if (!$uploadedFile) {
        $this->addFlash('error', 'Файл не загружен!');
        throw new \RuntimeException('Missing file');
    }

    // Валидация размера файла (макс 10МБ)
    if ($uploadedFile->getSize() > 10 * 1024 * 1024) {
        $this->addFlash('error', 'Файл слишком большой! Максимум 10МБ.');
        throw new \RuntimeException('File too large');
    }

    // Валидация MIME-типа (белый список)
    $allowedMimeTypes = [
        'image/jpeg', 'image/png', 'image/gif',
        'application/pdf', 'application/msword',
        'text/plain', 'video/mp4'
    ];

    if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes, true)) {
        $this->addFlash('error', 'Недопустимый тип файла!');
        throw new \RuntimeException('Invalid file type');
    }

    // Генерация уникального имени файла
    $fileName = md5(uniqid()) . '.' . $uploadedFile->guessExtension();

    // Перемещение файла в хранилище
    $uploadedFile->move(
        $this->getParameter('uploads_directory') . '/tasks',
        $fileName
    );

    // Установка свойств вложения
    $attachment->setFileName($fileName);
    $attachment->setOriginalName($uploadedFile->getClientOriginalName());
    $attachment->setMimeType($uploadedFile->getMimeType());
    $attachment->setFileSize($uploadedFile->getSize());
    $attachment->setFilePath('/uploads/tasks/' . $fileName);
    $attachment->determineFileType(); // Автоопределение из MIME
    $attachment->setUploadedAt(new \DateTimeImmutable());

    parent::persistEntity($em, $entityInstance);
}

public function deleteEntity(EntityManagerInterface $em, $entityInstance): void
{
    /** @var TaskAttachment $attachment */
    $attachment = $entityInstance;

    // Удаление файла из хранилища
    $filePath = $this->getParameter('kernel.project_dir') . '/public' . $attachment->getFilePath();

    if (file_exists($filePath)) {
        unlink($filePath);
        $this->addFlash('success', "Файл '{$attachment->getOriginalName()}' удален из хранилища.");
    }

    parent::deleteEntity($em, $entityInstance);
}
```

---

#### **Шаг 4: RecurrenceRuleCrudController** (2 часа)

**Сущность**: `App\Entity\RecurrenceRule`

**Сложность**: **СРЕДНЯЯ** (сложная визуализация логики повторения)

##### Конфигурация полей

| Поле | Тип | Видимо на | Сортируемо | Поиск | Заметки |
|-------|------|------------|----------|------------|-------|
| `id` | IdField | Index | ✅ | ❌ | Автогенерируемое |
| `createdBy` | AssociationField | All | ✅ | ✅ | Обязательное |
| `templateTask` | AssociationField | All | ✅ | ✅ | Обязательное, 1:1 |
| `recurrenceType` | ChoiceField | All | ✅ | ✅ | daily/weekly/monthly/yearly/custom |
| `interval` | IntegerField | Detail | ✅ | ❌ | Для кастомного типа |
| `daysOfWeek` | ArrayField | Detail | ❌ | ❌ | JSON, для еженедельного [1,2,3,4,5] |
| `dayOfMonth` | IntegerField | Detail | ✅ | ❌ | Для ежемесячного (1-31) |
| `monthOfYear` | IntegerField | Detail | ✅ | ❌ | Для ежегодного (1-12) |
| `endDate` | DateField | All | ✅ | ❌ | Nullable, остановить после даты |
| `maxOccurrences` | IntegerField | All | ✅ | ❌ | Nullable, макс количество повторений |
| `currentOccurrences` | IntegerField | All | ✅ | ❌ | Счетчик, только чтение |
| `nextOccurrenceDate` | DateTimeField | All | ✅ | ❌ | Дата следующей генерации |
| `timeOfDay` | TimeField | Detail | ❌ | ❌ | Время создания задачи |
| `isActive` | BooleanField | All | ✅ | ✅ | Переключатель Активно/Неактивно |
| `createdAt` | DateTimeField | Detail | ✅ | ❌ | Авто |
| `updatedAt` | DateTimeField | Detail | ✅ | ❌ | Авто |

##### Фильтры
- **Created By** (EntityFilter): Фильтр по пользователю
- **Template Task** (EntityFilter): Фильтр по задаче
- **Recurrence Type** (ChoiceFilter): daily/weekly/monthly/yearly/custom
- **Is Active** (BooleanFilter): Активно/Неактивно/Все
- **End Date Range** (DateTimeFilter): От/До
- **Next Occurrence Range** (DateTimeFilter): От/До
- **Has Reached Max** (BooleanFilter): currentOccurrences >= maxOccurrences

##### Действия
- **NEW**: Создать правило (иконка: plus, сложная форма)
- **EDIT**: Редактировать правило (иконка: edit)
- **DELETE**: Удалить правило (иконка: trash, подтверждение)
- **DETAIL**: Просмотр полных деталей с историей генерации (иконка: eye)
- **TOGGLE_ACTIVE**: Активировать/Деактивировать правило (кнопка действия)
- **TRIGGER_NOW**: Вручную запустить правило для генерации задачи (кнопка действия)
- **BATCH_ACTIVATE**: Активировать несколько правил (пакетное действие)
- **BATCH_DEACTIVATE**: Деактивировать несколько правил (пакетное действие)
- **EXPORT**: Экспорт правил со статистикой в CSV (пакетное действие)

##### Кастомное отображение

```php
public function configureFields(string $pageName): iterable
{
    $id = IdField::new('id');

    $createdBy = AssociationField::new('createdBy')
        ->autocomplete()
        ->setCrudController(UserCrudController::class)
        ->formatValue(fn ($value, RecurrenceRule $rule) =>
            $rule->getCreatedBy()->getEmail()
        );

    $templateTask = AssociationField::new('templateTask')
        ->autocomplete()
        ->setCrudController(TaskCrudController::class)
        ->formatValue(fn ($value, RecurrenceRule $rule) =>
            $rule->getTemplateTask()->getTitle()
        );

    $recurrenceType = ChoiceField::new('recurrenceType')
        ->setChoices([
            'Ежедневно' => RecurrenceRule::TYPE_DAILY,
            'Еженедельно' => RecurrenceRule::TYPE_WEEKLY,
            'Ежемесячно' => RecurrenceRule::TYPE_MONTHLY,
            'Ежегодно' => RecurrenceRule::TYPE_YEARLY,
            'Кастомное' => RecurrenceRule::TYPE_CUSTOM,
        ])
        ->renderAsBadges([
            RecurrenceRule::TYPE_DAILY => 'primary',
            RecurrenceRule::TYPE_WEEKLY => 'info',
            RecurrenceRule::TYPE_MONTHLY => 'success',
            RecurrenceRule::TYPE_YEARLY => 'warning',
            RecurrenceRule::TYPE_CUSTOM => 'secondary',
        ]);

    $isActive = BooleanField::new('isActive')
        ->renderAsSwitch(true);

    $progress = Field::new('progress', 'Прогресс')
        ->formatValue(function ($value, RecurrenceRule $rule) {
            if (!$rule->getMaxOccurrences()) {
                return $rule->getCurrentOccurrences() . ' / ∞';
            }

            $current = $rule->getCurrentOccurrences();
            $max = $rule->getMaxOccurrences();
            $percentage = ($current / $max) * 100;

            return sprintf(
                '%d / %d <div class="progress mt-1" style="height: 5px;">
                    <div class="progress-bar" style="width: %d%%"></div>
                </div>',
                $current,
                $max,
                $percentage
            );
        })
        ->onlyOnIndex();

    $nextOccurrenceDate = DateTimeField::new('nextOccurrenceDate')
        ->setFormat('dd.MM.yyyy HH:mm')
        ->setTimezone('Europe/Moscow');

    $daysOfWeek = ArrayField::new('daysOfWeek')
        ->formatValue(function ($value, RecurrenceRule $rule) {
            if (!$rule->getDaysOfWeek()) {
                return '-';
            }

            $daysMap = [
                1 => 'Пн', 2 => 'Вт', 3 => 'Ср',
                4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'
            ];

            $days = array_map(fn ($day) => $daysMap[$day], $rule->getDaysOfWeek());

            return implode(', ', $days);
        })
        ->onlyOnDetail();

    if (Crud::PAGE_INDEX === $pageName) {
        return [$id, $createdBy, $templateTask, $recurrenceType, $progress, $nextOccurrenceDate, $isActive];
    }

    return [/* все поля */];
}
```

##### Кастомные действия

```php
public function configureActions(Actions $actions): Actions
{
    $toggleActiveAction = Action::new('toggleActive', 'Переключить активность')
        ->linkToCrudAction('toggleActive')
        ->displayIf(static fn (RecurrenceRule $rule) => true)
        ->setIcon('fa fa-power-off');

    $triggerNowAction = Action::new('triggerNow', 'Сгенерировать задачу сейчас')
        ->linkToCrudAction('triggerNow')
        ->displayIf(static fn (RecurrenceRule $rule) => $rule->isActive())
        ->setIcon('fa fa-play')
        ->addCssClass('btn btn-success');

    return $actions
        ->add(Crud::PAGE_INDEX, $toggleActiveAction)
        ->add(Crud::PAGE_DETAIL, $toggleActiveAction)
        ->add(Crud::PAGE_DETAIL, $triggerNowAction);
}

public function toggleActive(AdminContext $context): Response
{
    $rule = $context->getEntity()->getInstance();
    $rule->setIsActive(!$rule->isActive());

    $this->entityManager->flush();

    $status = $rule->isActive() ? 'активировано' : 'деактивировано';
    $this->addFlash('success', "Правило {$status}!");

    return $this->redirect($context->getReferrer());
}

public function triggerNow(AdminContext $context, RecurrenceService $recurrenceService): Response
{
    /** @var RecurrenceRule $rule */
    $rule = $context->getEntity()->getInstance();

    try {
        $task = $recurrenceService->generateTaskFromRule($rule);

        $this->addFlash('success', "Задача '{$task->getTitle()}' успешно сгенерирована! (ID: {$task->getId()})");
    } catch (\Exception $e) {
        $this->addFlash('error', "Не удалось сгенерировать задачу: {$e->getMessage()}");
    }

    return $this->redirect($context->getReferrer());
}
```

---

### **ФАЗА 2: Поддерживающие сущности** ✅ **ЗАВЕРШЕНО** (3-4 часа)

**Дата завершения**: 2025-11-11
**Заметки по реализации**: Все три контроллера (MediaObject, RefreshToken, AuditLog) успешно реализованы с полной CRUD-функциональностью, фильтрами и кастомными действиями. Сущность AuditLog создана с нуля с автоматическим логгированием через event listener.

#### **Шаг 5: MediaObjectCrudController** (1.5 часа) ✅

**Сущность**: `App\Entity\MediaObject`

**Сложность**: **СРЕДНЯЯ** (аналогично TaskAttachment, но системная)

##### Конфигурация полей

| Поле | Тип | Видимо на | Сортируемо | Поиск | Заметки |
|-------|------|------------|----------|------------|-------|
| `id` | IdField | Index | ✅ | ❌ | Автогенерируемое |
| `uploadedBy` | AssociationField | All | ✅ | ✅ | Обязательное |
| `fileName` | TextField | All | ✅ | ✅ | Сгенерированное имя файла |
| `originalName` | TextField | All | ✅ | ✅ | Исходное имя файла |
| `mimeType` | TextField | All | ✅ | ✅ | MIME-тип файла |
| `fileType` | ChoiceField | All | ✅ | ✅ | image/document/video/other |
| `fileSize` | IntegerField | All | ✅ | ❌ | В байтах, форматированный вывод |
| `filePath` | TextField | Detail | ❌ | ❌ | Путь хранения, только чтение |
| `thumbnailPath` | TextField | Detail | ❌ | ❌ | Опциональная миниатюра |
| `createdAt` | DateTimeField | All | ✅ | ❌ | Авто |

*(Аналогичная реализация TaskAttachmentCrudController, опущена для краткости)*

---

#### **Шаг 6: RefreshTokenCrudController** (0.5 часа) ✅

**Сущность**: `App\Entity\RefreshToken`

**Сложность**: **НИЗКАЯ** (в основном просмотр в режиме только чтения)

##### Конфигурация полей

| Поле | Тип | Видимо на | Сортируемо | Поиск | Заметки |
|-------|------|------------|----------|------------|-------|
| `id` | IdField | Index | ✅ | ❌ | Автогенерируемое |
| `username` | TextField | All | ✅ | ✅ | Email пользователя (FK ограничение) |
| `refreshToken` | TextField | Detail | ❌ | ❌ | Хешированный токен, только чтение |
| `valid` | DateTimeField | All | ✅ | ❌ | Дата истечения |
| `isValid` | BooleanField | All | ✅ | ✅ | Вычисляемое: valid > now |

##### Фильтры
- **Username** (TextFilter): Частичное совпадение
- **Is Valid** (BooleanFilter): Действителен/Истек/Все
- **Valid Until Range** (DateTimeFilter): От/До

##### Действия
- **DETAIL**: Просмотр деталей токена (иконка: eye)
- **DELETE**: Отозвать токен (иконка: trash, принудительный выход)
- **BATCH_DELETE**: Отозвать несколько токенов (пакетное действие)
- **CLEANUP_EXPIRED**: Удалить все истекшие токены (глобальное действие)

##### Бизнес-логика

```php
public function configureActions(Actions $actions): Actions
{
    $cleanupExpiredAction = Action::new('cleanupExpired', 'Очистить истекшие токены')
        ->linkToCrudAction('cleanupExpired')
        ->createAsGlobalAction()
        ->setIcon('fa fa-broom')
        ->addCssClass('btn btn-warning');

    return $actions
        ->add(Crud::PAGE_INDEX, $cleanupExpiredAction);
}

public function cleanupExpired(AdminContext $context): Response
{
    $qb = $this->entityManager->createQueryBuilder();

    $count = $qb->delete(RefreshToken::class, 'rt')
        ->where('rt.valid < :now')
        ->setParameter('now', new \DateTime())
        ->getQuery()
        ->execute();

    $this->addFlash('success', "{$count} истекших токенов удалено!");

    return $this->redirect($context->getReferrer());
}
```

---

#### **Шаг 7: AuditLogCrudController** (2 часа) [НОВАЯ СУЩНОСТЬ] ✅

**Сущность**: `App\Entity\AuditLog` *(Успешно создана)*

**Назначение**: Отслеживать все действия администратора для безопасности и устранения неполадок

**Сложность**: **СРЕДНЯЯ** (новая сущность, event listeners)

##### Определение сущности

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(name: 'idx_audit_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_audit_entity', columns: ['entity_type', 'entity_id'])]
#[ORM\Index(name: 'idx_audit_action', columns: ['action'])]
#[ORM\Index(name: 'idx_audit_created_at', columns: ['created_at'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 100)]
    private string $action;  // CREATE, UPDATE, DELETE, LOGIN, LOGOUT

    #[ORM\Column(length: 100)]
    private string $entityType;  // Task, Tag, User, и т.д.

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $oldData = null;  // Состояние до (JSON)

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $newData = null;  // Состояние после (JSON)

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;  // IP, user agent, и т.д.

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Геттеры/Сеттеры...
}
```

##### Миграция

```bash
docker exec backend-php83 php bin/console make:migration
docker exec backend-php83 php bin/console doctrine:migrations:migrate
```

##### Event Listener (Автологирование)

```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
class AuditLogListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->logAction('CREATE', $args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->logAction('UPDATE', $args->getObject(), $args->getEntityChangeSet());
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $this->logAction('DELETE', $args->getObject());
    }

    private function logAction(string $action, object $entity, array $changeSet = []): void
    {
        // Пропустить логирование для самого AuditLog (предотвратить рекурсию)
        if ($entity instanceof AuditLog) {
            return;
        }

        // Логировать только действия администратора (из маршрутов /admin)
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !str_starts_with($request->getPathInfo(), '/admin')) {
            return;
        }

        $auditLog = new AuditLog();
        $auditLog->setUser($this->security->getUser());
        $auditLog->setAction($action);
        $auditLog->setEntityType((new \ReflectionClass($entity))->getShortName());

        if (method_exists($entity, 'getId')) {
            $auditLog->setEntityId($entity->getId());
        }

        if ('UPDATE' === $action) {
            $auditLog->setOldData($changeSet);
        }

        $auditLog->setNewData($this->serializeEntity($entity));
        $auditLog->setMetadata([
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'route' => $request->attributes->get('_route'),
        ]);

        $em = $this->doctrine->getManager();
        $em->persist($auditLog);
        $em->flush();
    }

    private function serializeEntity(object $entity): array
    {
        // Сериализация сущности в массив (упрощенно)
        $data = [];
        $reflection = new \ReflectionClass($entity);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($entity);

            // Упростить значение (избежать циклических ссылок)
            if (is_object($value)) {
                $data[$property->getName()] = method_exists($value, 'getId')
                    ? $value->getId()
                    : (string) $value;
            } else {
                $data[$property->getName()] = $value;
            }
        }

        return $data;
    }
}
```

##### CRUD контроллер

```php
public function configureFields(string $pageName): iterable
{
    $id = IdField::new('id');

    $user = AssociationField::new('user')
        ->formatValue(fn ($value, AuditLog $log) =>
            $log->getUser() ? $log->getUser()->getEmail() : 'Система'
        );

    $action = ChoiceField::new('action')
        ->setChoices([
            'Создание' => 'CREATE',
            'Обновление' => 'UPDATE',
            'Удаление' => 'DELETE',
            'Вход' => 'LOGIN',
            'Выход' => 'LOGOUT',
        ])
        ->renderAsBadges([
            'CREATE' => 'success',
            'UPDATE' => 'info',
            'DELETE' => 'danger',
            'LOGIN' => 'primary',
            'LOGOUT' => 'secondary',
        ]);

    $entityType = TextField::new('entityType')
        ->formatValue(fn ($value) => $value);

    $entityId = IntegerField::new('entityId')
        ->formatValue(function ($value, AuditLog $log) {
            if (!$log->getEntityId()) {
                return '-';
            }

            // Генерация ссылки на сущность
            return sprintf(
                '<a href="/admin?crudAction=detail&crudControllerFqcn=%sCrudController&entityId=%d">
                    #%d
                </a>',
                $log->getEntityType(),
                $log->getEntityId(),
                $log->getEntityId()
            );
        });

    $oldData = ArrayField::new('oldData')
        ->onlyOnDetail();

    $newData = ArrayField::new('newData')
        ->onlyOnDetail();

    $metadata = ArrayField::new('metadata')
        ->formatValue(function ($value, AuditLog $log) {
            $meta = $log->getMetadata() ?? [];
            return sprintf(
                'IP: %s<br>UA: %s',
                $meta['ip'] ?? '-',
                mb_substr($meta['user_agent'] ?? '-', 0, 50)
            );
        })
        ->onlyOnIndex();

    $createdAt = DateTimeField::new('createdAt')
        ->setFormat('dd.MM.yyyy HH:mm:ss');

    if (Crud::PAGE_INDEX === $pageName) {
        return [$id, $user, $action, $entityType, $entityId, $metadata, $createdAt];
    }

    return [/* все поля */];
}

public function configureCrud(Crud $crud): Crud
{
    return $crud
        ->setEntityLabelInSingular('Запись аудита')
        ->setEntityLabelInPlural('Записи аудита')
        ->setDefaultSort(['createdAt' => 'DESC'])
        ->setPaginatorPageSize(50)
        ->setPaginatorRangeSize(4)
        ->setPageTitle(Crud::PAGE_INDEX, 'Журнал активности')
        ->setSearchFields(['action', 'entityType', 'user.email'])
        ->setDateTimeFormat('dd.MM.yyyy HH:mm:ss')
        ->showEntityActionsInlined();
}

public function configureActions(Actions $actions): Actions
{
    // Только чтение: Только действие DETAIL
    return $actions
        ->disable(Action::NEW, Action::EDIT, Action::DELETE)
        ->add(Crud::PAGE_INDEX, Action::DETAIL);
}
```

---

### **ФАЗА 3: Дашборд и улучшения** ✅ **ЗАВЕРШЕНО** (3-4 часа)

**Дата завершения**: 2025-11-11
**Заметки по реализации**: Расширенный дашборд с метриками, графиками, уведомлениями. Меню обновлено с динамическими бейджами. Добавлена функциональность экспорта в CSV для TaskCrudController.

#### **Шаг 8: Расширенный дашборд** (2 часа) ✅

**Файл**: `src/Controller/Admin/DashboardController.php`

**Назначение**: Обзор системы с ключевыми метриками, графиками и быстрыми действиями

##### Виджеты дашборда

**1. Обзорные карточки статистики** (4 карточки)

```php
public function index(): Response
{
    // Получение метрик
    $userCount = $this->entityManager->getRepository(User::class)->count([]);
    $taskCount = $this->entityManager->getRepository(Task::class)->count([]);
    $activeRulesCount = $this->entityManager->getRepository(RecurrenceRule::class)->count(['isActive' => true]);

    // Расчет общего хранилища
    $qb = $this->entityManager->createQueryBuilder();
    $totalStorage = $qb->select('SUM(m.fileSize)')
        ->from(MediaObject::class, 'm')
        ->getQuery()
        ->getSingleScalarResult();

    $totalStorageMB = round($totalStorage / 1024 / 1024, 2);

    // Активность пользователей (последние 24ч)
    $yesterday = new \DateTimeImmutable('-24 hours');
    $activeUsersCount = $this->entityManager->createQueryBuilder()
        ->select('COUNT(DISTINCT al.user)')
        ->from(AuditLog::class, 'al')
        ->where('al.createdAt >= :yesterday')
        ->setParameter('yesterday', $yesterday)
        ->getQuery()
        ->getSingleScalarResult();

    // Процент завершенных задач (последние 30 дней)
    $thirtyDaysAgo = new \DateTimeImmutable('-30 days');
    $completedTasks = $this->entityManager->createQueryBuilder()
        ->select('COUNT(t.id)')
        ->from(Task::class, 't')
        ->where('t.status = :completed')
        ->andWhere('t.completedAt >= :thirtyDaysAgo')
        ->setParameter('completed', TaskStatus::COMPLETED)
        ->setParameter('thirtyDaysAgo', $thirtyDaysAgo)
        ->getQuery()
        ->getSingleScalarResult();

    $totalTasksLast30Days = $this->entityManager->createQueryBuilder()
        ->select('COUNT(t.id)')
        ->from(Task::class, 't')
        ->where('t.createdAt >= :thirtyDaysAgo')
        ->setParameter('thirtyDaysAgo', $thirtyDaysAgo)
        ->getQuery()
        ->getSingleScalarResult();

    $completionRate = $totalTasksLast30Days > 0
        ? round(($completedTasks / $totalTasksLast30Days) * 100, 1)
        : 0;

    // Количество просроченных задач
    $overdueTasksCount = $this->entityManager->createQueryBuilder()
        ->select('COUNT(t.id)')
        ->from(Task::class, 't')
        ->where('t.dueDate < :now')
        ->andWhere('t.status != :completed')
        ->andWhere('t.isArchived = false')
        ->setParameter('now', new \DateTimeImmutable())
        ->setParameter('completed', TaskStatus::COMPLETED)
        ->getQuery()
        ->getSingleScalarResult();

    return $this->render('admin/dashboard.html.twig', [
        'metrics' => [
            'users' => $userCount,
            'tasks' => $taskCount,
            'activeRules' => $activeRulesCount,
            'storage' => $totalStorageMB,
            'activeUsers24h' => $activeUsersCount,
            'completionRate' => $completionRate,
            'overdueTasks' => $overdueTasksCount,
        ],
    ]);
}
```

**2. График активности** (Последние 7 дней)

```php
// В DashboardController::index()

$activityData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = new \DateTimeImmutable("-{$i} days");
    $dateStr = $date->format('Y-m-d');

    $activityCount = $this->entityManager->createQueryBuilder()
        ->select('COUNT(al.id)')
        ->from(AuditLog::class, 'al')
        ->where('DATE(al.createdAt) = :date')
        ->setParameter('date', $dateStr)
        ->getQuery()
        ->getSingleScalarResult();

    $activityData[] = [
        'date' => $date->format('D, M j'),
        'count' => $activityCount,
    ];
}

return $this->render('admin/dashboard.html.twig', [
    'metrics' => [/* ... */],
    'activityChart' => $activityData,
]);
```

**3. Лента последней активности** (Последние 20 действий)

```php
$recentActivity = $this->entityManager->getRepository(AuditLog::class)
    ->createQueryBuilder('al')
    ->leftJoin('al.user', 'u')
    ->addSelect('u')
    ->orderBy('al.createdAt', 'DESC')
    ->setMaxResults(20)
    ->getQuery()
    ->getResult();

return $this->render('admin/dashboard.html.twig', [
    'metrics' => [/* ... */],
    'activityChart' => $activityData,
    'recentActivity' => $recentActivity,
]);
```

**4. Системные уведомления** (Проблемы, требующие внимания)

```php
$alerts = [];

// Уведомление: Истекшие refresh токены
$expiredTokensCount = $this->entityManager->createQueryBuilder()
    ->select('COUNT(rt.id)')
    ->from(RefreshToken::class, 'rt')
    ->where('rt.valid < :now')
    ->setParameter('now', new \DateTime())
    ->getQuery()
    ->getSingleScalarResult();

if ($expiredTokensCount > 100) {
    $alerts[] = [
        'type' => 'warning',
        'message' => "{$expiredTokensCount} истекших токенов требуют очистки",
        'action' => [
            'url' => $this->adminUrlGenerator->setController(RefreshTokenCrudController::class)->generateUrl(),
            'label' => 'Очистить сейчас',
        ],
    ];
}

// Уведомление: Высокое использование хранилища
if ($totalStorageMB > 500) {
    $alerts[] = [
        'type' => 'danger',
        'message' => "Использование хранилища высокое: {$totalStorageMB} МБ",
        'action' => [
            'url' => $this->adminUrlGenerator->setController(MediaObjectCrudController::class)->generateUrl(),
            'label' => 'Просмотреть файлы',
        ],
    ];
}

// Уведомление: Неактивные правила повторения
$inactiveRulesCount = $this->entityManager->getRepository(RecurrenceRule::class)->count(['isActive' => false]);

if ($inactiveRulesCount > 10) {
    $alerts[] = [
        'type' => 'info',
        'message' => "{$inactiveRulesCount} правил повторения неактивны",
        'action' => [
            'url' => $this->adminUrlGenerator->setController(RecurrenceRuleCrudController::class)->generateUrl(),
            'label' => 'Проверить правила',
        ],
    ];
}

return $this->render('admin/dashboard.html.twig', [
    'metrics' => [/* ... */],
    'activityChart' => $activityData,
    'recentActivity' => $recentActivity,
    'alerts' => $alerts,
]);
```

##### Шаблон дашборда

```twig
{# templates/admin/dashboard.html.twig #}
{% extends '@EasyAdmin/page/content.html.twig' %}

{% block content_title %}
    <h1>📊 Панель администратора</h1>
    <p class="text-muted">Обзор и метрики системы</p>
{% endblock %}

{% block main %}
    {# Системные уведомления #}
    {% if alerts is not empty %}
        <div class="mb-4">
            <h3>⚠️ Системные уведомления</h3>
            {% for alert in alerts %}
                <div class="alert alert-{{ alert.type }} d-flex justify-content-between align-items-center">
                    <span>{{ alert.message }}</span>
                    {% if alert.action %}
                        <a href="{{ alert.action.url }}" class="btn btn-sm btn-{{ alert.type }}">
                            {{ alert.action.label }}
                        </a>
                    {% endif %}
                </div>
            {% endfor %}
        </div>
    {% endif %}

    {# Обзорные карточки #}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">👥 Всего пользователей</h5>
                    <p class="card-text display-4">{{ metrics.users }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">📝 Всего задач</h5>
                    <p class="card-text display-4">{{ metrics.tasks }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">🔄 Активных правил</h5>
                    <p class="card-text display-4">{{ metrics.activeRules }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">💾 Хранилище (МБ)</h5>
                    <p class="card-text display-4">{{ metrics.storage }}</p>
                </div>
            </div>
        </div>
    </div>

    {# Вторичные метрики #}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">👤 Активных пользователей (24ч)</h6>
                    <p class="display-6">{{ metrics.activeUsers24h }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">✅ Процент завершения (30д)</h6>
                    <p class="display-6">{{ metrics.completionRate }}%</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">⏰ Просроченных задач</h6>
                    <p class="display-6">{{ metrics.overdueTasks }}</p>
                </div>
            </div>
        </div>
    </div>

    {# График активности #}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>📈 Активность администратора (Последние 7 дней)</h5>
                </div>
                <div class="card-body">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {# Лента последней активности #}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>🕒 Последняя активность</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Время</th>
                                <th>Пользователь</th>
                                <th>Действие</th>
                                <th>Сущность</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% for log in recentActivity %}
                                <tr>
                                    <td>{{ log.createdAt|date('H:i:s') }}</td>
                                    <td>{{ log.user ? log.user.email : 'Система' }}</td>
                                    <td>
                                        <span class="badge bg-{{ log.action == 'CREATE' ? 'success' : (log.action == 'DELETE' ? 'danger' : 'info') }}">
                                            {{ log.action }}
                                        </span>
                                    </td>
                                    <td>{{ log.entityType }} #{{ log.entityId }}</td>
                                </tr>
                            {% endfor %}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
{% endblock %}

{% block body_javascript %}
    {{ parent() }}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: {{ activityChart|map(a => a.date)|json_encode|raw }},
                datasets: [{
                    label: 'Действия администратора',
                    data: {{ activityChart|map(a => a.count)|json_encode|raw }},
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
{% endblock %}
```

---

#### **Шаг 9: Конфигурация меню** (0.5 часа)

**Файл**: `src/Controller/Admin/DashboardController.php`

```php
public function configureMenuItems(): iterable
{
    yield MenuItem::linkToDashboard('Дашборд', 'fa fa-home');

    yield MenuItem::section('Управление пользователями');
    yield MenuItem::linkToCrud('Пользователи', 'fa fa-users', User::class)
        ->setPermission('ROLE_ADMIN');

    yield MenuItem::section('Управление задачами');
    yield MenuItem::linkToCrud('Задачи', 'fa fa-tasks', Task::class)
        ->setPermission('ROLE_ADMIN')
        ->setBadge(
            fn () => $this->entityManager->getRepository(Task::class)->count([]),
            'info'
        );
    yield MenuItem::linkToCrud('Теги', 'fa fa-tags', Tag::class)
        ->setPermission('ROLE_ADMIN');
    yield MenuItem::linkToCrud('Повторяющиеся задачи', 'fa fa-sync', RecurrenceRule::class)
        ->setPermission('ROLE_ADMIN')
        ->setBadge(
            fn () => $this->entityManager->getRepository(RecurrenceRule::class)->count(['isActive' => true]),
            'success'
        );

    yield MenuItem::section('Медиа и файлы');
    yield MenuItem::linkToCrud('Вложения задач', 'fa fa-paperclip', TaskAttachment::class)
        ->setPermission('ROLE_ADMIN');
    yield MenuItem::linkToCrud('Медиа-библиотека', 'fa fa-images', MediaObject::class)
        ->setPermission('ROLE_ADMIN');

    yield MenuItem::section('Система');
    yield MenuItem::linkToCrud('Журнал аудита', 'fa fa-history', AuditLog::class)
        ->setPermission('ROLE_SUPER_ADMIN');
    yield MenuItem::linkToCrud('Токены обновления', 'fa fa-key', RefreshToken::class)
        ->setPermission('ROLE_SUPER_ADMIN');

    yield MenuItem::section('Быстрые действия');
    yield MenuItem::linkToRoute('Обработать правила повторения', 'fa fa-play-circle', 'admin_process_recurrence_rules')
        ->setPermission('ROLE_ADMIN');
    yield MenuItem::linkToRoute('Очистить истекшие токены', 'fa fa-broom', 'admin_cleanup_tokens')
        ->setPermission('ROLE_SUPER_ADMIN');

    yield MenuItem::section('');
    yield MenuItem::linkToUrl('Вернуться на сайт', 'fa fa-home', '/')
        ->setLinkTarget('_blank');
    yield MenuItem::linkToLogout('Выход', 'fa fa-sign-out-alt');
}
```

---

#### **Шаг 10: Массовые действия и экспорт** (1 час)

**Файл**: Кастомные пакетные действия в каждом CRUD-контроллере

##### Пример: Массовое завершение задач

```php
public function configureActions(Actions $actions): Actions
{
    $batchComplete = BatchAction::new('batchComplete', 'Завершить выбранные')
        ->linkToCrudAction('batchCompleteAction')
        ->addCssClass('btn btn-success')
        ->setIcon('fa fa-check');

    $batchArchive = BatchAction::new('batchArchive', 'Архивировать выбранные')
        ->linkToCrudAction('batchArchiveAction')
        ->addCssClass('btn btn-warning')
        ->setIcon('fa fa-archive');

    return $actions
        ->addBatchAction($batchComplete)
        ->addBatchAction($batchArchive);
}

public function batchCompleteAction(BatchActionDto $batchActionDto): Response
{
    $entityManager = $this->entityManager;
    $taskIds = $batchActionDto->getEntityIds();

    $qb = $entityManager->createQueryBuilder();
    $qb->update(Task::class, 't')
        ->set('t.status', ':completed')
        ->set('t.completedAt', ':now')
        ->where($qb->expr()->in('t.id', ':ids'))
        ->setParameter('completed', TaskStatus::COMPLETED)
        ->setParameter('now', new \DateTimeImmutable())
        ->setParameter('ids', $taskIds)
        ->getQuery()
        ->execute();

    $this->addFlash('success', count($taskIds) . ' задач завершено!');

    return $this->redirect($batchActionDto->getReferrerUrl());
}

public function batchArchiveAction(BatchActionDto $batchActionDto): Response
{
    $entityManager = $this->entityManager;
    $taskIds = $batchActionDto->getEntityIds();

    $qb = $entityManager->createQueryBuilder();
    $qb->update(Task::class, 't')
        ->set('t.isArchived', ':archived')
        ->where($qb->expr()->in('t.id', ':ids'))
        ->setParameter('archived', true)
        ->setParameter('ids', $taskIds)
        ->getQuery()
        ->execute();

    $this->addFlash('success', count($taskIds) . ' задач заархивировано!');

    return $this->redirect($batchActionDto->getReferrerUrl());
}
```

##### Экспорт в CSV (Универсальный)

```php
public function configureActions(Actions $actions): Actions
{
    $exportAction = Action::new('export', 'Экспорт CSV')
        ->linkToCrudAction('exportAction')
        ->createAsGlobalAction()
        ->addCssClass('btn btn-success')
        ->setIcon('fa fa-download');

    return $actions
        ->add(Crud::PAGE_INDEX, $exportAction);
}

public function exportAction(AdminContext $context): Response
{
    $filters = $this->getFilters($context);

    // Построение запроса с фильтрами
    $repository = $this->entityManager->getRepository(Task::class);
    $qb = $repository->createQueryBuilder('t');

    // Применение фильтров (упрощенно)
    if ($filters['status'] ?? null) {
        $qb->andWhere('t.status = :status')
           ->setParameter('status', $filters['status']);
    }

    $tasks = $qb->getQuery()->getResult();

    // Генерация CSV
    $csv = $this->generateCsv($tasks);

    $response = new Response($csv);
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="tasks_export_' . date('Y-m-d') . '.csv"');

    return $response;
}

private function generateCsv(array $tasks): string
{
    $output = fopen('php://temp', 'r+');

    // Заголовок
    fputcsv($output, ['ID', 'Название', 'Статус', 'Приоритет', 'Срок выполнения', 'Пользователь', 'Создано']);

    // Данные
    foreach ($tasks as $task) {
        fputcsv($output, [
            $task->getId(),
            $task->getTitle(),
            $task->getStatus()->value,
            $task->getPriority()->value,
            $task->getDueDate()?->format('Y-m-d H:i:s') ?? '-',
            $task->getUser()->getEmail(),
            $task->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);

    return $csv;
}
```

---

## 🔐 Система прав доступа (ROLE_ADMIN vs ROLE_SUPER_ADMIN)

### Иерархия ролей

```yaml
# config/packages/security.yaml
security:
    role_hierarchy:
        ROLE_ADMIN:       ROLE_USER
        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
```

### Матрица прав доступа

| Функция | ROLE_ADMIN | ROLE_SUPER_ADMIN |
|---------|------------|------------------|
| **Дашборд** | ✅ Просмотр | ✅ Просмотр |
| **Users CRUD** | ✅ Просмотр, Редактирование | ✅ Полный CRUD + Удаление |
| **Tasks CRUD** | ✅ Полный CRUD | ✅ Полный CRUD |
| **Tags CRUD** | ✅ Полный CRUD | ✅ Полный CRUD |
| **Attachments CRUD** | ✅ Полный CRUD | ✅ Полный CRUD |
| **Recurrence CRUD** | ✅ Полный CRUD | ✅ Полный CRUD |
| **Media CRUD** | ✅ Просмотр, Удаление | ✅ Полный CRUD |
| **Audit Logs** | ❌ Нет доступа | ✅ Только просмотр |
| **Refresh Tokens** | ❌ Нет доступа | ✅ Просмотр, Удаление, Очистка |
| **Настройки системы** | ❌ Нет доступа | ✅ Полный доступ |

### Реализация

```php
// В каждом CrudController
public function configureCrud(Crud $crud): Crud
{
    return $crud
        ->setEntityPermission('ROLE_ADMIN'); // Минимально требуемая роль
}

// Ограничение конкретных действий для SUPER_ADMIN
public function configureActions(Actions $actions): Actions
{
    return $actions
        ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ->setPermission(Action::BATCH_DELETE, 'ROLE_SUPER_ADMIN');
}

// В конфигурации меню
yield MenuItem::linkToCrud('Журнал аудита', 'fa fa-history', AuditLog::class)
    ->setPermission('ROLE_SUPER_ADMIN');
```

---

## 📦 Чек-лист реализации

### Фаза 1: Критические CRUD-контроллеры (6-8 часов)
- [ ] **Шаг 1**: TaskCrudController (3ч)
  - [ ] Создать класс контроллера
  - [ ] Настроить все поля (20 полей)
  - [ ] Добавить 10 фильтров
  - [ ] Реализовать кастомные действия (завершить, архивировать)
  - [ ] Добавить пакетные действия (завершить, архивировать, удалить)
  - [ ] Оптимизировать запросы (жадная загрузка)
  - [ ] Протестировать все CRUD операции

- [ ] **Шаг 2**: TagCrudController (1ч)
  - [ ] Создать класс контроллера
  - [ ] Настроить поля (9 полей)
  - [ ] Добавить 5 фильтров
  - [ ] Реализовать действие объединения
  - [ ] Добавить валидацию (уникальное имя на пользователя)
  - [ ] Протестировать функциональность объединения

- [ ] **Шаг 3**: TaskAttachmentCrudController (2ч)
  - [ ] Создать класс контроллера
  - [ ] Настроить поля (10 полей)
  - [ ] Добавить обработку загрузки файлов
  - [ ] Реализовать предпросмотр файлов
  - [ ] Добавить действие скачивания
  - [ ] Реализовать удаление файла из хранилища
  - [ ] Протестировать загрузку/скачивание/удаление

- [ ] **Шаг 4**: RecurrenceRuleCrudController (2ч)
  - [ ] Создать класс контроллера
  - [ ] Настроить поля (14 полей)
  - [ ] Добавить 8 фильтров
  - [ ] Реализовать действие переключения активности
  - [ ] Реализовать действие запуска сейчас
  - [ ] Добавить отображение прогресса
  - [ ] Протестировать интеграцию логики повторения

### Фаза 2: Поддерживающие сущности (3-4 часа)
- [ ] **Шаг 5**: MediaObjectCrudController (1.5ч)
  - [ ] Создать класс контроллера
  - [ ] Настроить поля
  - [ ] Реализовать обработку файлов
  - [ ] Добавить поддержку миниатюр
  - [ ] Протестировать операции с медиа

- [ ] **Шаг 6**: RefreshTokenCrudController (0.5ч)
  - [ ] Создать класс контроллера
  - [ ] Настроить поля только для чтения
  - [ ] Реализовать действие очистки
  - [ ] Протестировать отзыв токенов

- [ ] **Шаг 7**: AuditLogCrudController (2ч)
  - [ ] Создать сущность AuditLog
  - [ ] Создать миграцию
  - [ ] Реализовать event listener
  - [ ] Создать класс контроллера
  - [ ] Настроить отображение только для чтения
  - [ ] Протестировать автоматическое логирование
  - [ ] Проверить ленту активности

### Фаза 3: Дашборд и улучшения (3-4 часа)
- [ ] **Шаг 8**: Расширенный дашборд (2ч)
  - [ ] Реализовать расчет метрик
  - [ ] Создать шаблон дашборда
  - [ ] Добавить интеграцию Chart.js
  - [ ] Построить ленту активности
  - [ ] Создать системные уведомления
  - [ ] Протестировать рендеринг дашборда

- [ ] **Шаг 9**: Конфигурация меню (0.5ч)
  - [ ] Обновить структуру меню
  - [ ] Добавить разделители секций
  - [ ] Настроить права доступа
  - [ ] Добавить счетчики бейджей
  - [ ] Протестировать навигацию по меню

- [ ] **Шаг 10**: Массовые действия и экспорт (1ч)
  - [ ] Реализовать массовое завершение
  - [ ] Реализовать массовое архивирование
  - [ ] Реализовать массовое удаление
  - [ ] Создать экспорт CSV
  - [ ] Протестировать пакетные операции

### Финальные шаги
- [ ] Обновить docs/backend/INDEX.md с документацией админ-панели
- [ ] Создать руководство пользователя для администратора (опционально)
- [ ] Протестировать все функции end-to-end
- [ ] Аудит безопасности (проверки прав доступа)
- [ ] Оптимизация производительности (анализ запросов)
- [ ] Git commit с подробным сообщением

---

## 📈 Ожидаемые результаты

### Метрики после реализации

| Метрика | До | После | Улучшение |
|--------|--------|-------|-------------|
| **Управляемых сущностей** | 1 (User) | 8 (Все сущности) | +700% |
| **CRUD операций** | Базовый | Полный CRUD + Массовые | +200% |
| **Время решения поддержки** | 10+ мин | < 3 мин | -70% |
| **Видимость системы** | Нет | Полная | 100% |
| **Эффективность администратора** | Низкая | Высокая | +300% |

### Бизнес-влияние

1. **Команда поддержки**: Может решать проблемы пользователей за < 3 минуты (было 10+ минут)
2. **Здоровье системы**: Проактивный мониторинг через уведомления дашборда
3. **Целостность данных**: Журнал аудита всех действий администратора
4. **Масштабируемость**: Готово для 10К+ пользователей, 100К+ задач
5. **Поддерживаемость**: Чистый код, следующий принципам SOLID

---

## 🛠️ Технические заметки по реализации

### Оптимизация запросов

Все списковые запросы используют **жадную загрузку** для избежания проблем N+1:

```php
public function createIndexQueryBuilder(/* ... */): QueryBuilder
{
    return parent::createIndexQueryBuilder(/* ... */)
        ->leftJoin('entity.user', 'u')
        ->addSelect('u')
        ->leftJoin('entity.tags', 't')
        ->addSelect('t')
        // Жадная загрузка всех связей, отображаемых на index
        ->orderBy('entity.createdAt', 'DESC');
}
```

### Лучшие практики безопасности

1. **Защита CSRF**: Включена на всех формах
2. **Доступ на основе ролей**: Детальные права на действие
3. **Журнал аудита**: Все действия администратора логируются автоматически
4. **Валидация ввода**: Серверная валидация для всех полей
5. **Предотвращение XSS**: Twig автоматически экранирует весь вывод
6. **SQL-инъекции**: Doctrine ORM предотвращает SQL-инъекции

### Соображения производительности

1. **Пагинация**: Все списки пагинированы (20-50 элементов/страница)
2. **Индексированные запросы**: Использование существующих составных индексов
3. **Жадная загрузка**: Избежание N+1 запросов на связях
4. **Кеширование**: Рассмотреть Redis для метрик дашборда (опционально)
5. **Фоновые задачи**: Тяжелые операции (экспорт) через асинхронную очередь (опционально)

---

## 🎓 Обучающие ресурсы

### Документация EasyAdmin 4
- **Официальная документация**: https://symfony.com/bundles/EasyAdminBundle/current/index.html
- **Типы полей**: https://symfony.com/bundles/EasyAdminBundle/current/fields.html
- **Действия**: https://symfony.com/bundles/EasyAdminBundle/current/actions.html
- **Фильтры**: https://symfony.com/bundles/EasyAdminBundle/current/filters.html

### Лучшие практики Symfony
- **SOLID в Symfony**: https://symfony.com/doc/current/service_container.html
- **Безопасность**: https://symfony.com/doc/current/security.html
- **Производительность Doctrine**: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/improving-performance.html

---

## 🚀 Чек-лист запуска

Перед развертыванием в продакшн:

- [ ] Все CRUD-контроллеры протестированы
- [ ] Метрики дашборда проверены
- [ ] Логирование аудита работает
- [ ] Функциональность экспорта протестирована
- [ ] Пакетные действия протестированы
- [ ] Система прав доступа проверена
- [ ] N+1 запросы устранены
- [ ] Аудит безопасности пройден
- [ ] Документация обновлена
- [ ] Создан пользователь-администратор с ROLE_SUPER_ADMIN
- [ ] Резервная копия базы данных перед первым использованием

---

## 📞 Поддержка и устранение неполадок

### Распространенные проблемы

**1. Ошибки "Undefined index" в конфигурации Field**
- **Причина**: Доступ к массиву без проверки существования
- **Исправление**: Использовать `$pageData['key'] ?? null` вместо `$pageData['key']`

**2. Проблемы производительности N+1 запросов**
- **Причина**: Отсутствует жадная загрузка в QueryBuilder
- **Исправление**: Добавить `leftJoin()` + `addSelect()` для всех связей

**3. Ошибки отказа в доступе**
- **Причина**: Отсутствует роль ROLE_ADMIN
- **Исправление**: Обновить роли пользователя в базе данных или через UserCrudController

**4. Не удается загрузить файл**
- **Причина**: Отсутствует директория uploads или неправильные права доступа
- **Исправление**: Создать директорию и установить права 755: `mkdir -p public/uploads/tasks && chmod 755 public/uploads/tasks`

**5. Медленные метрики дашборда**
- **Причина**: Сложные агрегационные запросы
- **Исправление**: Добавить индексы или реализовать кеширование (Redis)

---

## 🎯 Критерии успеха

✅ **MVP (Минимально жизнеспособный продукт):**
- Все 8 CRUD-контроллеров функциональны
- Дашборд с базовыми метриками
- Пользователь может создавать/редактировать/удалять все сущности
- Нет проблем N+1 запросов

✅ **Полная реализация:**
- Все кастомные действия работают (завершить, архивировать, объединить и т.д.)
- Логирование аудита функционально
- Массовые действия работают
- Экспорт в CSV работает
- Система прав доступа применяется
- Дашборд с графиками и уведомлениями

✅ **Корпоративный уровень:**
- Производительность оптимизирована (< 100мс среднее время запроса)
- Аудит безопасности пройден
- Документация завершена
- Готово к продакшн

---

**Версия документа**: 1.0
**Последнее обновление**: 2025-11-10
**Общее оценочное время**: 12-15 часов
**Сложность**: Средне-высокая
**Технологии**: Symfony 7.1, EasyAdmin 4.18, PHP 8.3, PostgreSQL 16

**Готово к реализации!** 🚀
