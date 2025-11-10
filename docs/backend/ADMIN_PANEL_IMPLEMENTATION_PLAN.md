# 🎛️ EasyAdmin Panel - Enterprise Implementation Plan

> **Version**: 1.0
> **Date**: 2025-11-10
> **Status**: Ready for Implementation
> **Estimated Time**: 12-15 hours (solo dev + AI assistance)

---

## 📋 Executive Summary

This document outlines a **complete enterprise-grade admin panel** implementation for the Task Management System using EasyAdmin 4.18. The admin panel will provide comprehensive CRUD operations, advanced filtering, system monitoring, user activity tracking, and technical support capabilities.

### Current State
- ✅ Basic admin panel with User CRUD (fully functional)
- ✅ Authentication system with ROLE_ADMIN requirement
- ✅ Modern custom login page
- ✅ Security configured properly

### Target State
A **full-featured admin panel** with:
- 🎯 **8 Entity CRUD Controllers** (User, Task, Tag, RecurrenceRule, MediaObject, TaskAttachment, RefreshToken, plus AuditLog)
- 📊 **Dashboard with System Metrics** (users, tasks, storage, activity)
- 🔍 **Advanced Search & Filtering** (across all entities)
- 📈 **User Activity Monitoring** (audit trail for all actions)
- 🛠️ **Technical Support Tools** (bulk operations, data export, user impersonation)
- 🔐 **Permission System** (ROLE_ADMIN, ROLE_SUPER_ADMIN)
- 📤 **Export Functionality** (CSV, Excel export for all entities)

---

## 🎯 Goals and Requirements

### Business Goals
1. **Support Team Efficiency**: Enable quick resolution of user issues without database access
2. **Data Visibility**: Monitor all app activity and user actions in real-time
3. **Content Moderation**: Ability to manage and moderate user-generated content
4. **System Health**: Track system metrics and identify issues early
5. **User Management**: Complete user lifecycle management (activation, suspension, deletion)

### Technical Requirements
- **SOLID Principles**: Follow existing codebase patterns
- **Type Safety**: Full PHP 8.3 type hints everywhere
- **Security**: Role-based access with granular permissions
- **Performance**: Optimized queries with proper indexing
- **Maintainability**: Clear separation of concerns, DRY principle
- **Documentation**: Comprehensive inline documentation

### Use Cases (Technical Support Scenarios)

#### Scenario 1: User Reports Bug with Tags
**Issue**: "Help! I can't delete 2 tags from my task 'Buy Milk'"

**Admin Workflow**:
1. Search for user by email in UserCrudController
2. Click on user → View their tasks (associated field)
3. Find task "Buy Milk" (search by title)
4. Open Task details → View related Tags
5. Remove problematic tags or delete/reassign them
6. **Resolution Time**: < 2 minutes

#### Scenario 2: User Lost Access to Tasks
**Issue**: "All my tasks disappeared after login"

**Admin Workflow**:
1. Find user in UserCrudController
2. View user's tasks (OneToMany relationship)
3. Check if tasks are archived (`isArchived = true`)
4. Bulk unarchive tasks using batch action
5. Verify in frontend
6. **Resolution Time**: < 1 minute

#### Scenario 3: Recurring Task Not Generating
**Issue**: "My daily task stopped creating automatically"

**Admin Workflow**:
1. Find user's RecurrenceRule in RecurrenceRuleCrudController
2. Check `isActive` status (might be deactivated)
3. View `currentOccurrences` vs `maxOccurrences` (might be reached)
4. Check `nextOccurrenceDate` (might be in past due to cron failure)
5. Manually update rule or trigger recurrence command
6. **Resolution Time**: < 3 minutes

#### Scenario 4: System Performance Analysis
**Admin Workflow**:
1. Go to Dashboard
2. View system metrics (task count, user count, media storage)
3. Check AuditLog for suspicious activity (mass deletions, errors)
4. Identify heavy users (TaskCrudController → filter by user → count)
5. Optimize or contact user
6. **Resolution Time**: < 5 minutes

---

## 🏗️ Architecture Design

### Entity Hierarchy (by Priority)

#### **Phase 1: Critical Entities** (6-8 hours)
1. **TaskCrudController** - Core feature (30% of work)
2. **TagCrudController** - User categorization (10% of work)
3. **TaskAttachmentCrudController** - File management (15% of work)
4. **RecurrenceRuleCrudController** - Recurring tasks (15% of work)

#### **Phase 2: Supporting Entities** (3-4 hours)
5. **MediaObjectCrudController** - Media library (10% of work)
6. **RefreshTokenCrudController** - Session management (5% of work)
7. **AuditLogCrudController** - Activity tracking (NEW - 15% of work)

#### **Phase 3: Dashboard & Enhancements** (3-4 hours)
8. **Dashboard Metrics** - System overview (10% of work)
9. **Bulk Actions** - Batch operations (5% of work)
10. **Export Functionality** - Data export (5% of work)

### Entity Relationship Map for Admin

```
DashboardController
├── UserCrudController [EXISTING ✅]
│   ├── 1:N → TaskCrudController [NEW]
│   ├── 1:N → TagCrudController [NEW]
│   ├── 1:N → MediaObjectCrudController [NEW]
│   ├── 1:N → RecurrenceRuleCrudController [NEW]
│   └── 1:N → AuditLogCrudController [NEW]
│
├── TaskCrudController [NEW]
│   ├── N:1 → UserCrudController (owner)
│   ├── 1:N → TaskCrudController (subtasks - self-referencing)
│   ├── M:N → TagCrudController (tags)
│   ├── 1:N → TaskAttachmentCrudController (attachments)
│   ├── 1:1 → RecurrenceRuleCrudController (template)
│   └── N:1 → RecurrenceRuleCrudController (generated_from)
│
├── TagCrudController [NEW]
│   ├── N:1 → UserCrudController (owner)
│   └── M:N → TaskCrudController (tasks)
│
├── RecurrenceRuleCrudController [NEW]
│   ├── N:1 → UserCrudController (created_by)
│   └── 1:1 → TaskCrudController (template_task)
│
├── TaskAttachmentCrudController [NEW]
│   ├── N:1 → TaskCrudController (task)
│   └── N:1 → UserCrudController (uploaded_by)
│
├── MediaObjectCrudController [NEW]
│   └── N:1 → UserCrudController (uploaded_by)
│
├── RefreshTokenCrudController [NEW]
│   └── N:1 → UserCrudController (via username)
│
└── AuditLogCrudController [NEW]
    ├── N:1 → UserCrudController (user who performed action)
    └── Polymorphic → Any Entity (entity_type + entity_id)
```

---

## 📊 Implementation Plan - Step-by-Step

### **PHASE 1: Critical CRUD Controllers** (6-8 hours)

#### **Step 1: TaskCrudController** (3 hours)

**Entity**: `App\Entity\Task`

**Complexity**: **HIGH** (most complex due to nested relationships)

##### Fields Configuration

| Field | Type | Visible On | Sortable | Searchable | Notes |
|-------|------|------------|----------|------------|-------|
| `id` | IdField | Index | ✅ | ❌ | Auto-generated |
| `user` | AssociationField | All | ✅ | ✅ | Required, autocomplete |
| `parentTask` | AssociationField | All | ✅ | ✅ | Nullable, self-ref |
| `title` | TextField | All | ✅ | ✅ | Required, max 255 |
| `description` | TextareaField | Detail/Form | ❌ | ✅ | Nullable, max 5000 |
| `status` | ChoiceField | All | ✅ | ✅ | Enum: PENDING/IN_PROGRESS/COMPLETED/CANCELLED |
| `priority` | ChoiceField | All | ✅ | ✅ | Enum: LOW/MEDIUM/HIGH/URGENT |
| `startDate` | DateTimeField | All | ✅ | ❌ | Nullable |
| `dueDate` | DateTimeField | All | ✅ | ❌ | Nullable |
| `completedAt` | DateTimeField | Detail | ✅ | ❌ | Auto-set, nullable |
| `sortOrder` | IntegerField | Detail | ✅ | ❌ | Default 0 |
| `isArchived` | BooleanField | All | ✅ | ✅ | Default false |
| `isRecurringTemplate` | BooleanField | Detail | ✅ | ✅ | Default false |
| `tags` | AssociationField | All | ❌ | ❌ | M:N, autocomplete |
| `subtasks` | CollectionField | Detail | ❌ | ❌ | 1:N, readonly |
| `attachments` | CollectionField | Detail | ❌ | ❌ | 1:N, readonly |
| `recurrenceRule` | AssociationField | Detail | ❌ | ❌ | 1:1, nullable |
| `generatedFromRule` | AssociationField | Detail | ❌ | ❌ | N:1, nullable |
| `createdAt` | DateTimeField | Detail | ✅ | ❌ | Auto |
| `updatedAt` | DateTimeField | Detail | ✅ | ❌ | Auto |

##### Filters
- **User** (EntityFilter): Filter by owner
- **Status** (ChoiceFilter): Multiple selection
- **Priority** (ChoiceFilter): Multiple selection
- **Is Archived** (BooleanFilter): True/False/All
- **Is Recurring Template** (BooleanFilter): True/False/All
- **Tags** (EntityFilter): Filter by associated tags
- **Due Date Range** (DateTimeFilter): From/To
- **Created At Range** (DateTimeFilter): From/To
- **Has Subtasks** (BooleanFilter): Tasks with/without subtasks
- **Parent Task** (EntityFilter): Filter by parent

##### Actions
- **NEW**: Create task (icon: plus)
- **EDIT**: Edit task (icon: edit)
- **DELETE**: Delete task and all subtasks (icon: trash, confirmation required)
- **DETAIL**: View full details (icon: eye)
- **BATCH_COMPLETE**: Mark multiple tasks as completed (batch action)
- **BATCH_ARCHIVE**: Archive multiple tasks (batch action)
- **BATCH_DELETE**: Delete multiple tasks (batch action, confirmation required)
- **EXPORT**: Export selected tasks to CSV/Excel (batch action)

##### Business Logic Hooks

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

    $this->addFlash('success', "Task '{$task->getTitle()}' completed!");

    return $this->redirect($context->getReferrer());
}

public function archiveTask(AdminContext $context): Response
{
    $task = $context->getEntity()->getInstance();
    $task->setIsArchived(true);

    $this->entityManager->flush();

    $this->addFlash('success', "Task '{$task->getTitle()}' archived!");

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
        // Eager load to avoid N+1 queries
        ->orderBy('entity.createdAt', 'DESC');
}
```

##### Custom Form Validation

```php
public function persistEntity(EntityManagerInterface $em, $entityInstance): void
{
    /** @var Task $task */
    $task = $entityInstance;

    // Validation: startDate < dueDate
    if ($task->getStartDate() && $task->getDueDate()) {
        if ($task->getStartDate() > $task->getDueDate()) {
            $this->addFlash('error', 'Start date must be before due date!');
            throw new \RuntimeException('Invalid dates');
        }
    }

    // Validation: Parent task cannot be itself
    if ($task->getParentTask() && $task->getParentTask()->getId() === $task->getId()) {
        $this->addFlash('error', 'Task cannot be its own parent!');
        throw new \RuntimeException('Invalid parent');
    }

    parent::persistEntity($em, $entityInstance);
}
```

##### Display Optimization

```php
public function configureFields(string $pageName): iterable
{
    // Optimize field display based on page
    $id = IdField::new('id');
    $user = AssociationField::new('user')
        ->autocomplete()
        ->setCrudController(UserCrudController::class)
        ->formatValue(fn ($value, Task $task) => $task->getUser()->getEmail());

    $title = TextField::new('title')
        ->setMaxLength(50); // Truncate on index

    $status = ChoiceField::new('status')
        ->setChoices([
            'Pending' => TaskStatus::PENDING,
            'In Progress' => TaskStatus::IN_PROGRESS,
            'Completed' => TaskStatus::COMPLETED,
            'Cancelled' => TaskStatus::CANCELLED,
        ])
        ->renderAsBadges([
            TaskStatus::PENDING->value => 'secondary',
            TaskStatus::IN_PROGRESS->value => 'primary',
            TaskStatus::COMPLETED->value => 'success',
            TaskStatus::CANCELLED->value => 'danger',
        ]);

    $priority = ChoiceField::new('priority')
        ->setChoices([
            'Low' => TaskPriority::LOW,
            'Medium' => TaskPriority::MEDIUM,
            'High' => TaskPriority::HIGH,
            'Urgent' => TaskPriority::URGENT,
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

    // Return fields based on page
    if (Crud::PAGE_INDEX === $pageName) {
        return [$id, $user, $title, $status, $priority, $dueDate, $isArchived, $tags, $subtaskCount];
    }

    if (Crud::PAGE_DETAIL === $pageName) {
        return [/* all fields with associations */];
    }

    return [/* form fields */];
}
```

---

#### **Step 2: TagCrudController** (1 hour)

**Entity**: `App\Entity\Tag`

**Complexity**: **LOW** (simple entity with basic M:N relationship)

##### Fields Configuration

| Field | Type | Visible On | Sortable | Searchable | Notes |
|-------|------|------------|----------|------------|-------|
| `id` | IdField | Index | ✅ | ❌ | Auto-generated |
| `user` | AssociationField | All | ✅ | ✅ | Required, autocomplete |
| `name` | TextField | All | ✅ | ✅ | Required, max 50, unique per user |
| `color` | ColorField | All | ✅ | ❌ | Hex color (#RRGGBB), default #3B82F6 |
| `icon` | TextField | All | ❌ | ✅ | Nullable, icon name |
| `usageCount` | IntegerField | All | ✅ | ❌ | Counter, readonly |
| `tasks` | AssociationField | Detail | ❌ | ❌ | M:N, readonly collection |
| `createdAt` | DateTimeField | Detail | ✅ | ❌ | Auto |
| `updatedAt` | DateTimeField | Detail | ✅ | ❌ | Auto |

##### Filters
- **User** (EntityFilter): Filter by owner
- **Name** (TextFilter): Partial match
- **Usage Count Range** (NumberFilter): Min/Max
- **Created At Range** (DateTimeFilter): From/To
- **Has Tasks** (BooleanFilter): Tags with/without associated tasks

##### Actions
- **NEW**: Create tag (icon: plus, color picker)
- **EDIT**: Edit tag (icon: edit)
- **DELETE**: Delete tag (icon: trash, confirmation with task count warning)
- **DETAIL**: View full details including task list (icon: eye)
- **MERGE**: Merge multiple tags into one (batch action, custom modal)
- **BATCH_DELETE**: Delete multiple tags (batch action, confirmation required)
- **EXPORT**: Export tags with usage stats to CSV (batch action)

##### Business Logic Hooks

```php
public function persistEntity(EntityManagerInterface $em, $entityInstance): void
{
    /** @var Tag $tag */
    $tag = $entityInstance;

    // Validation: Name + User must be unique
    $existingTag = $em->getRepository(Tag::class)->findOneBy([
        'name' => $tag->getName(),
        'user' => $tag->getUser(),
    ]);

    if ($existingTag && $existingTag->getId() !== $tag->getId()) {
        $this->addFlash('error', "Tag '{$tag->getName()}' already exists for this user!");
        throw new \RuntimeException('Duplicate tag name');
    }

    // Validation: Color format
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $tag->getColor())) {
        $this->addFlash('error', 'Invalid color format! Use hex format (#RRGGBB)');
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
        $this->addFlash('warning', "Tag '{$tag->getName()}' removed from {$taskCount} tasks.");
    }

    parent::deleteEntity($em, $entityInstance);
}
```

##### Custom Action: Merge Tags

```php
public function configureActions(Actions $actions): Actions
{
    $mergeAction = Action::new('mergeTags', 'Merge Tags')
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
        $this->addFlash('error', 'Select at least 2 tags to merge!');
        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    // Render custom merge form
    $form = $this->createForm(TagMergeType::class, [
        'source_tags' => $tagIds,
    ]);

    $form->handleRequest($this->requestStack->getCurrentRequest());

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        $targetTagId = $data['target_tag'];

        $targetTag = $this->entityManager->find(Tag::class, $targetTagId);
        $sourceTags = $this->entityManager->getRepository(Tag::class)->findBy(['id' => $tagIds]);

        // Move all tasks from source tags to target tag
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

        $this->addFlash('success', 'Tags merged successfully!');

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    return $this->render('admin/tag/merge.html.twig', [
        'form' => $form->createView(),
        'tags' => $this->entityManager->getRepository(Tag::class)->findBy(['id' => $tagIds]),
    ]);
}
```

---

#### **Step 3: TaskAttachmentCrudController** (2 hours)

**Entity**: `App\Entity\TaskAttachment`

**Complexity**: **MEDIUM** (file handling, storage management)

##### Fields Configuration

| Field | Type | Visible On | Sortable | Searchable | Notes |
|-------|------|------------|----------|------------|-------|
| `id` | IdField | Index | ✅ | ❌ | Auto-generated |
| `task` | AssociationField | All | ✅ | ✅ | Required |
| `uploadedBy` | AssociationField | All | ✅ | ✅ | Required |
| `fileName` | TextField | All | ✅ | ✅ | Generated filename |
| `originalName` | TextField | All | ✅ | ✅ | Original filename |
| `mimeType` | TextField | All | ✅ | ✅ | File MIME type |
| `fileType` | ChoiceField | All | ✅ | ✅ | image/document/video/other |
| `fileSize` | IntegerField | All | ✅ | ❌ | In bytes, formatted display |
| `filePath` | TextField | Detail | ❌ | ❌ | Storage path, readonly |
| `uploadedAt` | DateTimeField | All | ✅ | ❌ | Auto |

##### Filters
- **Task** (EntityFilter): Filter by parent task
- **Uploaded By** (EntityFilter): Filter by uploader
- **File Type** (ChoiceFilter): image/document/video/other
- **MIME Type** (TextFilter): Partial match
- **File Size Range** (NumberFilter): Min/Max (MB)
- **Uploaded At Range** (DateTimeFilter): From/To

##### Actions
- **NEW**: Upload attachment (icon: upload)
- **DETAIL**: View details with preview (icon: eye)
- **DELETE**: Delete attachment and file from storage (icon: trash, confirmation)
- **DOWNLOAD**: Download file (action button)
- **BATCH_DELETE**: Delete multiple attachments (batch action, confirmation)
- **EXPORT**: Export attachment metadata to CSV (batch action)

##### Custom Display

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
            'Image' => 'image',
            'Document' => 'document',
            'Video' => 'video',
            'Other' => 'other',
        ])
        ->renderAsBadges([
            'image' => 'success',
            'document' => 'primary',
            'video' => 'warning',
            'other' => 'secondary',
        ]);

    $preview = ImageField::new('filePath', 'Preview')
        ->setBasePath('/uploads/tasks/')
        ->onlyWhen('image' === $pageData['fileType'] ?? null)
        ->onlyOnDetail();

    $downloadLink = Field::new('download', 'Download')
        ->formatValue(function ($value, TaskAttachment $attachment) {
            return sprintf(
                '<a href="/uploads/tasks/%s" download="%s" class="btn btn-sm btn-primary">
                    <i class="fa fa-download"></i> Download
                </a>',
                $attachment->getFileName(),
                $attachment->getOriginalName()
            );
        })
        ->onlyOnDetail();

    if (Crud::PAGE_INDEX === $pageName) {
        return [$id, $task, $uploadedBy, $originalName, $fileType, $fileSize, $uploadedAt];
    }

    return [/* all fields */];
}
```

##### File Upload Handling

```php
public function persistEntity(EntityManagerInterface $em, $entityInstance): void
{
    /** @var TaskAttachment $attachment */
    $attachment = $entityInstance;

    // Handle file upload (assumes UploadedFile in form)
    $uploadedFile = $this->requestStack->getCurrentRequest()->files->get('file');

    if (!$uploadedFile) {
        $this->addFlash('error', 'No file uploaded!');
        throw new \RuntimeException('Missing file');
    }

    // Validate file size (max 10MB)
    if ($uploadedFile->getSize() > 10 * 1024 * 1024) {
        $this->addFlash('error', 'File too large! Max 10MB allowed.');
        throw new \RuntimeException('File too large');
    }

    // Validate MIME type (whitelist)
    $allowedMimeTypes = [
        'image/jpeg', 'image/png', 'image/gif',
        'application/pdf', 'application/msword',
        'text/plain', 'video/mp4'
    ];

    if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes, true)) {
        $this->addFlash('error', 'Invalid file type!');
        throw new \RuntimeException('Invalid file type');
    }

    // Generate unique filename
    $fileName = md5(uniqid()) . '.' . $uploadedFile->guessExtension();

    // Move file to storage
    $uploadedFile->move(
        $this->getParameter('uploads_directory') . '/tasks',
        $fileName
    );

    // Set attachment properties
    $attachment->setFileName($fileName);
    $attachment->setOriginalName($uploadedFile->getClientOriginalName());
    $attachment->setMimeType($uploadedFile->getMimeType());
    $attachment->setFileSize($uploadedFile->getSize());
    $attachment->setFilePath('/uploads/tasks/' . $fileName);
    $attachment->determineFileType(); // Auto-detect from MIME
    $attachment->setUploadedAt(new \DateTimeImmutable());

    parent::persistEntity($em, $entityInstance);
}

public function deleteEntity(EntityManagerInterface $em, $entityInstance): void
{
    /** @var TaskAttachment $attachment */
    $attachment = $entityInstance;

    // Delete file from storage
    $filePath = $this->getParameter('kernel.project_dir') . '/public' . $attachment->getFilePath();

    if (file_exists($filePath)) {
        unlink($filePath);
        $this->addFlash('success', "File '{$attachment->getOriginalName()}' deleted from storage.");
    }

    parent::deleteEntity($em, $entityInstance);
}
```

---

#### **Step 4: RecurrenceRuleCrudController** (2 hours)

**Entity**: `App\Entity\RecurrenceRule`

**Complexity**: **MEDIUM** (complex recurrence logic visualization)

##### Fields Configuration

| Field | Type | Visible On | Sortable | Searchable | Notes |
|-------|------|------------|----------|------------|-------|
| `id` | IdField | Index | ✅ | ❌ | Auto-generated |
| `createdBy` | AssociationField | All | ✅ | ✅ | Required |
| `templateTask` | AssociationField | All | ✅ | ✅ | Required, 1:1 |
| `recurrenceType` | ChoiceField | All | ✅ | ✅ | daily/weekly/monthly/yearly/custom |
| `interval` | IntegerField | Detail | ✅ | ❌ | For custom type |
| `daysOfWeek` | ArrayField | Detail | ❌ | ❌ | JSON, for weekly [1,2,3,4,5] |
| `dayOfMonth` | IntegerField | Detail | ✅ | ❌ | For monthly (1-31) |
| `monthOfYear` | IntegerField | Detail | ✅ | ❌ | For yearly (1-12) |
| `endDate` | DateField | All | ✅ | ❌ | Nullable, stop after date |
| `maxOccurrences` | IntegerField | All | ✅ | ❌ | Nullable, max times to occur |
| `currentOccurrences` | IntegerField | All | ✅ | ❌ | Counter, readonly |
| `nextOccurrenceDate` | DateTimeField | All | ✅ | ❌ | Next generation date |
| `timeOfDay` | TimeField | Detail | ❌ | ❌ | Time when task created |
| `isActive` | BooleanField | All | ✅ | ✅ | Active/Inactive toggle |
| `createdAt` | DateTimeField | Detail | ✅ | ❌ | Auto |
| `updatedAt` | DateTimeField | Detail | ✅ | ❌ | Auto |

##### Filters
- **Created By** (EntityFilter): Filter by user
- **Template Task** (EntityFilter): Filter by task
- **Recurrence Type** (ChoiceFilter): daily/weekly/monthly/yearly/custom
- **Is Active** (BooleanFilter): Active/Inactive/All
- **End Date Range** (DateTimeFilter): From/To
- **Next Occurrence Range** (DateTimeFilter): From/To
- **Has Reached Max** (BooleanFilter): currentOccurrences >= maxOccurrences

##### Actions
- **NEW**: Create rule (icon: plus, complex form)
- **EDIT**: Edit rule (icon: edit)
- **DELETE**: Delete rule (icon: trash, confirmation)
- **DETAIL**: View full details with generation history (icon: eye)
- **TOGGLE_ACTIVE**: Activate/Deactivate rule (action button)
- **TRIGGER_NOW**: Manually trigger rule to generate task (action button)
- **BATCH_ACTIVATE**: Activate multiple rules (batch action)
- **BATCH_DEACTIVATE**: Deactivate multiple rules (batch action)
- **EXPORT**: Export rules with stats to CSV (batch action)

##### Custom Display

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
            'Daily' => RecurrenceRule::TYPE_DAILY,
            'Weekly' => RecurrenceRule::TYPE_WEEKLY,
            'Monthly' => RecurrenceRule::TYPE_MONTHLY,
            'Yearly' => RecurrenceRule::TYPE_YEARLY,
            'Custom' => RecurrenceRule::TYPE_CUSTOM,
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

    $progress = Field::new('progress', 'Progress')
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
                1 => 'Mon', 2 => 'Tue', 3 => 'Wed',
                4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'
            ];

            $days = array_map(fn ($day) => $daysMap[$day], $rule->getDaysOfWeek());

            return implode(', ', $days);
        })
        ->onlyOnDetail();

    if (Crud::PAGE_INDEX === $pageName) {
        return [$id, $createdBy, $templateTask, $recurrenceType, $progress, $nextOccurrenceDate, $isActive];
    }

    return [/* all fields */];
}
```

##### Custom Actions

```php
public function configureActions(Actions $actions): Actions
{
    $toggleActiveAction = Action::new('toggleActive', 'Toggle Active')
        ->linkToCrudAction('toggleActive')
        ->displayIf(static fn (RecurrenceRule $rule) => true)
        ->setIcon('fa fa-power-off');

    $triggerNowAction = Action::new('triggerNow', 'Generate Task Now')
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

    $status = $rule->isActive() ? 'activated' : 'deactivated';
    $this->addFlash('success', "Rule {$status}!");

    return $this->redirect($context->getReferrer());
}

public function triggerNow(AdminContext $context, RecurrenceService $recurrenceService): Response
{
    /** @var RecurrenceRule $rule */
    $rule = $context->getEntity()->getInstance();

    try {
        $task = $recurrenceService->generateTaskFromRule($rule);

        $this->addFlash('success', "Task '{$task->getTitle()}' generated successfully! (ID: {$task->getId()})");
    } catch (\Exception $e) {
        $this->addFlash('error', "Failed to generate task: {$e->getMessage()}");
    }

    return $this->redirect($context->getReferrer());
}
```

---

### **PHASE 2: Supporting Entities** (3-4 hours)

#### **Step 5: MediaObjectCrudController** (1.5 hours)

**Entity**: `App\Entity\MediaObject`

**Complexity**: **MEDIUM** (similar to TaskAttachment but system-wide)

##### Fields Configuration

| Field | Type | Visible On | Sortable | Searchable | Notes |
|-------|------|------------|----------|------------|-------|
| `id` | IdField | Index | ✅ | ❌ | Auto-generated |
| `uploadedBy` | AssociationField | All | ✅ | ✅ | Required |
| `fileName` | TextField | All | ✅ | ✅ | Generated filename |
| `originalName` | TextField | All | ✅ | ✅ | Original filename |
| `mimeType` | TextField | All | ✅ | ✅ | File MIME type |
| `fileType` | ChoiceField | All | ✅ | ✅ | image/document/video/other |
| `fileSize` | IntegerField | All | ✅ | ❌ | In bytes, formatted display |
| `filePath` | TextField | Detail | ❌ | ❌ | Storage path, readonly |
| `thumbnailPath` | TextField | Detail | ❌ | ❌ | Optional thumbnail |
| `createdAt` | DateTimeField | All | ✅ | ❌ | Auto |

*(Similar implementation to TaskAttachmentCrudController, omitted for brevity)*

---

#### **Step 6: RefreshTokenCrudController** (0.5 hours)

**Entity**: `App\Entity\RefreshToken`

**Complexity**: **LOW** (mostly read-only view)

##### Fields Configuration

| Field | Type | Visible On | Sortable | Searchable | Notes |
|-------|------|------------|----------|------------|-------|
| `id` | IdField | Index | ✅ | ❌ | Auto-generated |
| `username` | TextField | All | ✅ | ✅ | User email (FK constraint) |
| `refreshToken` | TextField | Detail | ❌ | ❌ | Hashed token, readonly |
| `valid` | DateTimeField | All | ✅ | ❌ | Expiration datetime |
| `isValid` | BooleanField | All | ✅ | ✅ | Computed: valid > now |

##### Filters
- **Username** (TextFilter): Partial match
- **Is Valid** (BooleanFilter): Valid/Expired/All
- **Valid Until Range** (DateTimeFilter): From/To

##### Actions
- **DETAIL**: View token details (icon: eye)
- **DELETE**: Revoke token (icon: trash, force logout)
- **BATCH_DELETE**: Revoke multiple tokens (batch action)
- **CLEANUP_EXPIRED**: Delete all expired tokens (global action)

##### Business Logic

```php
public function configureActions(Actions $actions): Actions
{
    $cleanupExpiredAction = Action::new('cleanupExpired', 'Cleanup Expired Tokens')
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

    $this->addFlash('success', "{$count} expired tokens deleted!");

    return $this->redirect($context->getReferrer());
}
```

---

#### **Step 7: AuditLogCrudController** (2 hours) [NEW ENTITY]

**Entity**: `App\Entity\AuditLog` *(To be created)*

**Purpose**: Track all admin actions for security and troubleshooting

**Complexity**: **MEDIUM** (new entity, event listeners)

##### Entity Definition

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
    private string $entityType;  // Task, Tag, User, etc.

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $oldData = null;  // Before state (JSON)

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $newData = null;  // After state (JSON)

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;  // IP, user agent, etc.

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters/Setters...
}
```

##### Migration

```bash
docker exec backend-php83 php bin/console make:migration
docker exec backend-php83 php bin/console doctrine:migrations:migrate
```

##### Event Listener (Auto-logging)

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
        // Skip logging for AuditLog itself (prevent recursion)
        if ($entity instanceof AuditLog) {
            return;
        }

        // Only log admin actions (from /admin routes)
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
        // Serialize entity to array (simplified)
        $data = [];
        $reflection = new \ReflectionClass($entity);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($entity);

            // Simplify value (avoid circular references)
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

##### CRUD Controller

```php
public function configureFields(string $pageName): iterable
{
    $id = IdField::new('id');

    $user = AssociationField::new('user')
        ->formatValue(fn ($value, AuditLog $log) =>
            $log->getUser() ? $log->getUser()->getEmail() : 'System'
        );

    $action = ChoiceField::new('action')
        ->setChoices([
            'Create' => 'CREATE',
            'Update' => 'UPDATE',
            'Delete' => 'DELETE',
            'Login' => 'LOGIN',
            'Logout' => 'LOGOUT',
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

            // Generate link to entity
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

    return [/* all fields */];
}

public function configureCrud(Crud $crud): Crud
{
    return $crud
        ->setEntityLabelInSingular('Audit Log')
        ->setEntityLabelInPlural('Audit Logs')
        ->setDefaultSort(['createdAt' => 'DESC'])
        ->setPaginatorPageSize(50)
        ->setPaginatorRangeSize(4)
        ->setPageTitle(Crud::PAGE_INDEX, 'Activity Audit Trail')
        ->setSearchFields(['action', 'entityType', 'user.email'])
        ->setDateTimeFormat('dd.MM.yyyy HH:mm:ss')
        ->showEntityActionsInlined();
}

public function configureActions(Actions $actions): Actions
{
    // Read-only: Only DETAIL action
    return $actions
        ->disable(Action::NEW, Action::EDIT, Action::DELETE)
        ->add(Crud::PAGE_INDEX, Action::DETAIL);
}
```

---

### **PHASE 3: Dashboard & Enhancements** (3-4 hours)

#### **Step 8: Enhanced Dashboard** (2 hours)

**File**: `src/Controller/Admin/DashboardController.php`

**Purpose**: System overview with key metrics, charts, and quick actions

##### Dashboard Widgets

**1. Overview Statistics Cards** (4 cards)

```php
public function index(): Response
{
    // Fetch metrics
    $userCount = $this->entityManager->getRepository(User::class)->count([]);
    $taskCount = $this->entityManager->getRepository(Task::class)->count([]);
    $activeRulesCount = $this->entityManager->getRepository(RecurrenceRule::class)->count(['isActive' => true]);

    // Calculate total storage
    $qb = $this->entityManager->createQueryBuilder();
    $totalStorage = $qb->select('SUM(m.fileSize)')
        ->from(MediaObject::class, 'm')
        ->getQuery()
        ->getSingleScalarResult();

    $totalStorageMB = round($totalStorage / 1024 / 1024, 2);

    // User activity (last 24h)
    $yesterday = new \DateTimeImmutable('-24 hours');
    $activeUsersCount = $this->entityManager->createQueryBuilder()
        ->select('COUNT(DISTINCT al.user)')
        ->from(AuditLog::class, 'al')
        ->where('al.createdAt >= :yesterday')
        ->setParameter('yesterday', $yesterday)
        ->getQuery()
        ->getSingleScalarResult();

    // Task completion rate (last 30 days)
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

    // Overdue tasks count
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

**2. Activity Chart** (Last 7 days)

```php
// In DashboardController::index()

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

**3. Recent Activity Feed** (Last 20 actions)

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

**4. System Alerts** (Issues requiring attention)

```php
$alerts = [];

// Alert: Expired refresh tokens
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
        'message' => "{$expiredTokensCount} expired refresh tokens need cleanup",
        'action' => [
            'url' => $this->adminUrlGenerator->setController(RefreshTokenCrudController::class)->generateUrl(),
            'label' => 'Cleanup Now',
        ],
    ];
}

// Alert: High storage usage
if ($totalStorageMB > 500) {
    $alerts[] = [
        'type' => 'danger',
        'message' => "Storage usage is high: {$totalStorageMB} MB",
        'action' => [
            'url' => $this->adminUrlGenerator->setController(MediaObjectCrudController::class)->generateUrl(),
            'label' => 'View Files',
        ],
    ];
}

// Alert: Inactive recurrence rules
$inactiveRulesCount = $this->entityManager->getRepository(RecurrenceRule::class)->count(['isActive' => false]);

if ($inactiveRulesCount > 10) {
    $alerts[] = [
        'type' => 'info',
        'message' => "{$inactiveRulesCount} recurrence rules are inactive",
        'action' => [
            'url' => $this->adminUrlGenerator->setController(RecurrenceRuleCrudController::class)->generateUrl(),
            'label' => 'Review Rules',
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

##### Dashboard Template

```twig
{# templates/admin/dashboard.html.twig #}
{% extends '@EasyAdmin/page/content.html.twig' %}

{% block content_title %}
    <h1>📊 Admin Dashboard</h1>
    <p class="text-muted">System Overview & Metrics</p>
{% endblock %}

{% block main %}
    {# System Alerts #}
    {% if alerts is not empty %}
        <div class="mb-4">
            <h3>⚠️ System Alerts</h3>
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

    {# Overview Cards #}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">👥 Total Users</h5>
                    <p class="card-text display-4">{{ metrics.users }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">📝 Total Tasks</h5>
                    <p class="card-text display-4">{{ metrics.tasks }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">🔄 Active Rules</h5>
                    <p class="card-text display-4">{{ metrics.activeRules }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">💾 Storage (MB)</h5>
                    <p class="card-text display-4">{{ metrics.storage }}</p>
                </div>
            </div>
        </div>
    </div>

    {# Secondary Metrics #}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">👤 Active Users (24h)</h6>
                    <p class="display-6">{{ metrics.activeUsers24h }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">✅ Completion Rate (30d)</h6>
                    <p class="display-6">{{ metrics.completionRate }}%</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">⏰ Overdue Tasks</h6>
                    <p class="display-6">{{ metrics.overdueTasks }}</p>
                </div>
            </div>
        </div>
    </div>

    {# Activity Chart #}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>📈 Admin Activity (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {# Recent Activity Feed #}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>🕒 Recent Activity</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% for log in recentActivity %}
                                <tr>
                                    <td>{{ log.createdAt|date('H:i:s') }}</td>
                                    <td>{{ log.user ? log.user.email : 'System' }}</td>
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
                    label: 'Admin Actions',
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

#### **Step 9: Menu Configuration** (0.5 hours)

**File**: `src/Controller/Admin/DashboardController.php`

```php
public function configureMenuItems(): iterable
{
    yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

    yield MenuItem::section('User Management');
    yield MenuItem::linkToCrud('Users', 'fa fa-users', User::class)
        ->setPermission('ROLE_ADMIN');

    yield MenuItem::section('Task Management');
    yield MenuItem::linkToCrud('Tasks', 'fa fa-tasks', Task::class)
        ->setPermission('ROLE_ADMIN')
        ->setBadge(
            fn () => $this->entityManager->getRepository(Task::class)->count([]),
            'info'
        );
    yield MenuItem::linkToCrud('Tags', 'fa fa-tags', Tag::class)
        ->setPermission('ROLE_ADMIN');
    yield MenuItem::linkToCrud('Recurring Tasks', 'fa fa-sync', RecurrenceRule::class)
        ->setPermission('ROLE_ADMIN')
        ->setBadge(
            fn () => $this->entityManager->getRepository(RecurrenceRule::class)->count(['isActive' => true]),
            'success'
        );

    yield MenuItem::section('Media & Files');
    yield MenuItem::linkToCrud('Task Attachments', 'fa fa-paperclip', TaskAttachment::class)
        ->setPermission('ROLE_ADMIN');
    yield MenuItem::linkToCrud('Media Library', 'fa fa-images', MediaObject::class)
        ->setPermission('ROLE_ADMIN');

    yield MenuItem::section('System');
    yield MenuItem::linkToCrud('Audit Logs', 'fa fa-history', AuditLog::class)
        ->setPermission('ROLE_SUPER_ADMIN');
    yield MenuItem::linkToCrud('Refresh Tokens', 'fa fa-key', RefreshToken::class)
        ->setPermission('ROLE_SUPER_ADMIN');

    yield MenuItem::section('Quick Actions');
    yield MenuItem::linkToRoute('Process Recurrence Rules', 'fa fa-play-circle', 'admin_process_recurrence_rules')
        ->setPermission('ROLE_ADMIN');
    yield MenuItem::linkToRoute('Cleanup Expired Tokens', 'fa fa-broom', 'admin_cleanup_tokens')
        ->setPermission('ROLE_SUPER_ADMIN');

    yield MenuItem::section('');
    yield MenuItem::linkToUrl('Back to Main Site', 'fa fa-home', '/')
        ->setLinkTarget('_blank');
    yield MenuItem::linkToLogout('Logout', 'fa fa-sign-out-alt');
}
```

---

#### **Step 10: Bulk Actions & Export** (1 hour)

**File**: Custom batch actions in each CRUD controller

##### Example: Bulk Complete Tasks

```php
public function configureActions(Actions $actions): Actions
{
    $batchComplete = BatchAction::new('batchComplete', 'Complete Selected')
        ->linkToCrudAction('batchCompleteAction')
        ->addCssClass('btn btn-success')
        ->setIcon('fa fa-check');

    $batchArchive = BatchAction::new('batchArchive', 'Archive Selected')
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

    $this->addFlash('success', count($taskIds) . ' tasks completed!');

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

    $this->addFlash('success', count($taskIds) . ' tasks archived!');

    return $this->redirect($batchActionDto->getReferrerUrl());
}
```

##### Export to CSV (Generic)

```php
public function configureActions(Actions $actions): Actions
{
    $exportAction = Action::new('export', 'Export CSV')
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

    // Build query with filters
    $repository = $this->entityManager->getRepository(Task::class);
    $qb = $repository->createQueryBuilder('t');

    // Apply filters (simplified)
    if ($filters['status'] ?? null) {
        $qb->andWhere('t.status = :status')
           ->setParameter('status', $filters['status']);
    }

    $tasks = $qb->getQuery()->getResult();

    // Generate CSV
    $csv = $this->generateCsv($tasks);

    $response = new Response($csv);
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="tasks_export_' . date('Y-m-d') . '.csv"');

    return $response;
}

private function generateCsv(array $tasks): string
{
    $output = fopen('php://temp', 'r+');

    // Header
    fputcsv($output, ['ID', 'Title', 'Status', 'Priority', 'Due Date', 'User', 'Created At']);

    // Data
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

## 🔐 Permission System (ROLE_ADMIN vs ROLE_SUPER_ADMIN)

### Role Hierarchy

```yaml
# config/packages/security.yaml
security:
    role_hierarchy:
        ROLE_ADMIN:       ROLE_USER
        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
```

### Permission Matrix

| Feature | ROLE_ADMIN | ROLE_SUPER_ADMIN |
|---------|------------|------------------|
| **Dashboard** | ✅ View | ✅ View |
| **Users CRUD** | ✅ View, Edit | ✅ Full CRUD + Delete |
| **Tasks CRUD** | ✅ Full CRUD | ✅ Full CRUD |
| **Tags CRUD** | ✅ Full CRUD | ✅ Full CRUD |
| **Attachments CRUD** | ✅ Full CRUD | ✅ Full CRUD |
| **Recurrence CRUD** | ✅ Full CRUD | ✅ Full CRUD |
| **Media CRUD** | ✅ View, Delete | ✅ Full CRUD |
| **Audit Logs** | ❌ No Access | ✅ View Only |
| **Refresh Tokens** | ❌ No Access | ✅ View, Delete, Cleanup |
| **System Settings** | ❌ No Access | ✅ Full Access |

### Implementation

```php
// In each CrudController
public function configureCrud(Crud $crud): Crud
{
    return $crud
        ->setEntityPermission('ROLE_ADMIN'); // Minimum required role
}

// Restrict specific actions to SUPER_ADMIN
public function configureActions(Actions $actions): Actions
{
    return $actions
        ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ->setPermission(Action::BATCH_DELETE, 'ROLE_SUPER_ADMIN');
}

// In menu configuration
yield MenuItem::linkToCrud('Audit Logs', 'fa fa-history', AuditLog::class)
    ->setPermission('ROLE_SUPER_ADMIN');
```

---

## 📦 Implementation Checklist

### Phase 1: Critical CRUD Controllers (6-8 hours)
- [ ] **Step 1**: TaskCrudController (3h)
  - [ ] Create controller class
  - [ ] Configure all fields (20 fields)
  - [ ] Add 10 filters
  - [ ] Implement custom actions (complete, archive)
  - [ ] Add batch actions (complete, archive, delete)
  - [ ] Optimize queries (eager loading)
  - [ ] Test all CRUD operations

- [ ] **Step 2**: TagCrudController (1h)
  - [ ] Create controller class
  - [ ] Configure fields (9 fields)
  - [ ] Add 5 filters
  - [ ] Implement merge action
  - [ ] Add validation (unique name per user)
  - [ ] Test merge functionality

- [ ] **Step 3**: TaskAttachmentCrudController (2h)
  - [ ] Create controller class
  - [ ] Configure fields (10 fields)
  - [ ] Add file upload handling
  - [ ] Implement file preview
  - [ ] Add download action
  - [ ] Implement file deletion from storage
  - [ ] Test upload/download/delete

- [ ] **Step 4**: RecurrenceRuleCrudController (2h)
  - [ ] Create controller class
  - [ ] Configure fields (14 fields)
  - [ ] Add 8 filters
  - [ ] Implement toggle active action
  - [ ] Implement trigger now action
  - [ ] Add progress display
  - [ ] Test recurrence logic integration

### Phase 2: Supporting Entities (3-4 hours)
- [ ] **Step 5**: MediaObjectCrudController (1.5h)
  - [ ] Create controller class
  - [ ] Configure fields
  - [ ] Implement file handling
  - [ ] Add thumbnail support
  - [ ] Test media operations

- [ ] **Step 6**: RefreshTokenCrudController (0.5h)
  - [ ] Create controller class
  - [ ] Configure read-only fields
  - [ ] Implement cleanup action
  - [ ] Test token revocation

- [ ] **Step 7**: AuditLogCrudController (2h)
  - [ ] Create AuditLog entity
  - [ ] Create migration
  - [ ] Implement event listener
  - [ ] Create controller class
  - [ ] Configure read-only display
  - [ ] Test auto-logging
  - [ ] Verify activity feed

### Phase 3: Dashboard & Enhancements (3-4 hours)
- [ ] **Step 8**: Enhanced Dashboard (2h)
  - [ ] Implement metrics calculation
  - [ ] Create dashboard template
  - [ ] Add Chart.js integration
  - [ ] Build activity feed
  - [ ] Create system alerts
  - [ ] Test dashboard rendering

- [ ] **Step 9**: Menu Configuration (0.5h)
  - [ ] Update menu structure
  - [ ] Add section dividers
  - [ ] Configure permissions
  - [ ] Add badge counters
  - [ ] Test menu navigation

- [ ] **Step 10**: Bulk Actions & Export (1h)
  - [ ] Implement bulk complete
  - [ ] Implement bulk archive
  - [ ] Implement bulk delete
  - [ ] Create CSV export
  - [ ] Test batch operations

### Final Steps
- [ ] Update docs/backend/INDEX.md with admin panel documentation
- [ ] Create admin user guide (optional)
- [ ] Test all features end-to-end
- [ ] Security audit (permission checks)
- [ ] Performance optimization (query analysis)
- [ ] Git commit with comprehensive message

---

## 📈 Expected Outcomes

### Metrics After Implementation

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| **Managed Entities** | 1 (User) | 8 (All entities) | +700% |
| **CRUD Operations** | Basic | Full CRUD + Bulk | +200% |
| **Support Resolution Time** | 10+ min | < 3 min | -70% |
| **System Visibility** | None | Complete | 100% |
| **Admin Efficiency** | Low | High | +300% |

### Business Impact

1. **Support Team**: Can resolve user issues in < 3 minutes (was 10+ minutes)
2. **System Health**: Proactive monitoring via dashboard alerts
3. **Data Integrity**: Audit trail for all admin actions
4. **Scalability**: Ready for 10K+ users, 100K+ tasks
5. **Maintainability**: Clean code following SOLID principles

---

## 🛠️ Technical Implementation Notes

### Query Optimization

All list queries use **eager loading** to avoid N+1 problems:

```php
public function createIndexQueryBuilder(/* ... */): QueryBuilder
{
    return parent::createIndexQueryBuilder(/* ... */)
        ->leftJoin('entity.user', 'u')
        ->addSelect('u')
        ->leftJoin('entity.tags', 't')
        ->addSelect('t')
        // Eager load all associations displayed on index
        ->orderBy('entity.createdAt', 'DESC');
}
```

### Security Best Practices

1. **CSRF Protection**: Enabled on all forms
2. **Role-Based Access**: Granular permissions per action
3. **Audit Trail**: All admin actions logged automatically
4. **Input Validation**: Server-side validation for all fields
5. **XSS Prevention**: Twig auto-escapes all output
6. **SQL Injection**: Doctrine ORM prevents SQL injection

### Performance Considerations

1. **Pagination**: All lists paginated (20-50 items/page)
2. **Indexed Queries**: Use existing composite indexes
3. **Eager Loading**: Avoid N+1 queries on associations
4. **Caching**: Consider Redis for dashboard metrics (optional)
5. **Background Jobs**: Heavy operations (export) via async queue (optional)

---

## 🎓 Learning Resources

### EasyAdmin 4 Documentation
- **Official Docs**: https://symfony.com/bundles/EasyAdminBundle/current/index.html
- **Field Types**: https://symfony.com/bundles/EasyAdminBundle/current/fields.html
- **Actions**: https://symfony.com/bundles/EasyAdminBundle/current/actions.html
- **Filters**: https://symfony.com/bundles/EasyAdminBundle/current/filters.html

### Symfony Best Practices
- **SOLID in Symfony**: https://symfony.com/doc/current/service_container.html
- **Security**: https://symfony.com/doc/current/security.html
- **Doctrine Performance**: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/improving-performance.html

---

## 🚀 Go-Live Checklist

Before deploying to production:

- [ ] All CRUD controllers tested
- [ ] Dashboard metrics verified
- [ ] Audit logging working
- [ ] Export functionality tested
- [ ] Batch actions tested
- [ ] Permission system verified
- [ ] N+1 queries eliminated
- [ ] Security audit passed
- [ ] Documentation updated
- [ ] Admin user created with ROLE_SUPER_ADMIN
- [ ] Backup database before first use

---

## 📞 Support & Troubleshooting

### Common Issues

**1. "Undefined index" errors in Field configuration**
- **Cause**: Accessing array without checking existence
- **Fix**: Use `$pageData['key'] ?? null` instead of `$pageData['key']`

**2. N+1 query performance**
- **Cause**: Missing eager loading in QueryBuilder
- **Fix**: Add `leftJoin()` + `addSelect()` for all associations

**3. Permission denied errors**
- **Cause**: Missing ROLE_ADMIN role
- **Fix**: Update user roles in database or via UserCrudController

**4. File upload fails**
- **Cause**: Missing uploads directory or wrong permissions
- **Fix**: Create directory and set 755 permissions: `mkdir -p public/uploads/tasks && chmod 755 public/uploads/tasks`

**5. Dashboard metrics slow**
- **Cause**: Complex aggregation queries
- **Fix**: Add indexes or implement caching (Redis)

---

## 🎯 Success Criteria

✅ **MVP (Minimum Viable Product):**
- All 8 CRUD controllers functional
- Dashboard with basic metrics
- User can create/edit/delete all entities
- No N+1 query problems

✅ **Complete Implementation:**
- All custom actions working (complete, archive, merge, etc.)
- Audit logging functional
- Bulk actions working
- Export to CSV working
- Permission system enforced
- Dashboard with charts and alerts

✅ **Enterprise-Grade:**
- Performance optimized (< 100ms avg query time)
- Security audit passed
- Documentation complete
- Production-ready

---

**Document Version**: 1.0
**Last Updated**: 2025-11-10
**Total Estimated Time**: 12-15 hours
**Complexity**: Medium-High
**Technology**: Symfony 7.1, EasyAdmin 4.18, PHP 8.3, PostgreSQL 16

**Ready for Implementation!** 🚀
