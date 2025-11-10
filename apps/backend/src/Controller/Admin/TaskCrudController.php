<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\HttpFoundation\Response;

class TaskCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Task::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Task')
            ->setEntityLabelInPlural('Tasks')
            ->setPageTitle('index', 'Task Management')
            ->setPageTitle('new', 'Create new Task')
            ->setPageTitle('edit', fn (Task $task) => sprintf('Edit: %s', $task->getTitle()))
            ->setPageTitle('detail', fn (Task $task) => sprintf('Task: %s', $task->getTitle()))

            // Pagination - reduced for performance
            ->setPaginatorPageSize(15)
            ->setPaginatorRangeSize(3)

            // Search
            ->setSearchFields(['title', 'description', 'user.email'])

            // Default sort: newest first
            ->setDefaultSort(['createdAt' => 'DESC'])

            // Date format
            ->setDateTimeFormat('dd.MM.yyyy HH:mm')
            ->setDateFormat('dd.MM.yyyy')

            // Other settings
            ->showEntityActionsInlined()
            ->setEntityPermission('ROLE_ADMIN');
    }

    public function configureFields(string $pageName): iterable
    {
        // ID field - only on index
        yield IdField::new('id', 'ID')
            ->onlyOnIndex();

        // User (owner) - required association with autocomplete
        yield AssociationField::new('user', 'User')
            ->setRequired(true)
            ->autocomplete()
            ->setCrudController(UserCrudController::class)
            ->formatValue(function ($value, Task $task) {
                return $task->getUser() ? $task->getUser()->getEmail() : '-';
            })
            ->setHelp('Task owner')
            ->setColumns('col-md-6');

        // Title - required, searchable
        yield TextField::new('title', 'Title')
            ->setRequired(true)
            ->setMaxLength(Crud::PAGE_INDEX === $pageName ? 60 : 255)
            ->setHelp('Task title (max 255 characters)')
            ->setColumns('col-md-6');

        // Description - textarea on forms, hidden on index
        if (Crud::PAGE_INDEX !== $pageName) {
            yield TextareaField::new('description', 'Description')
                ->setHelp('Task description (max 5000 characters)')
                ->setMaxLength(5000)
                ->hideOnIndex();
        }

        // Status - enum with badges
        yield ChoiceField::new('status', 'Status')
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
            ])
            ->setColumns('col-md-3');

        // Priority - enum with badges
        yield ChoiceField::new('priority', 'Priority')
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
            ])
            ->setColumns('col-md-3');

        // Parent Task - self-referencing association
        if (Crud::PAGE_INDEX !== $pageName) {
            yield AssociationField::new('parentTask', 'Parent Task')
                ->autocomplete()
                ->setHelp('Optional: set parent task for subtask relationship')
                ->setFormTypeOption('query_builder', function ($repository) {
                    return $repository->createQueryBuilder('t')
                        ->where('t.isRecurringTemplate = false')
                        ->orderBy('t.title', 'ASC');
                })
                ->hideOnIndex()
                ->setColumns('col-md-6');
        }

        // Tags - simplified for INDEX, detailed for DETAIL/EDIT
        if (Crud::PAGE_INDEX === $pageName) {
            // On INDEX - just show count (no N+1 queries)
            yield IntegerField::new('tagsCount', 'Tags')
                ->formatValue(function ($value, Task $task) {
                    $count = $task->getTags()->count();
                    if ($count === 0) {
                        return '<span class="text-muted">-</span>';
                    }
                    return sprintf('<span class="badge badge-info">%d tag%s</span>', $count, $count > 1 ? 's' : '');
                })
                ->onlyOnIndex();
        } else {
            // On DETAIL/EDIT - show full formatted tags
            yield AssociationField::new('tags', 'Tags')
                ->autocomplete()
                ->formatValue(function ($value, Task $task) {
                    $tags = $task->getTags();
                    if ($tags->isEmpty()) {
                        return '<span class="text-muted">No tags</span>';
                    }

                    $tagNames = [];
                    foreach ($tags as $tag) {
                        $tagNames[] = sprintf(
                            '<span class="badge" style="background-color: %s; color: white;">%s</span>',
                            htmlspecialchars($tag->getColor()),
                            htmlspecialchars($tag->getName())
                        );
                    }

                    return implode(' ', $tagNames);
                })
                ->setHelp('Associated tags')
                ->setColumns('col-md-12')
                ->hideOnIndex();
        }

        // Dates
        yield DateTimeField::new('startDate', 'Start Date')
            ->setHelp('Optional: when to start the task')
            ->setColumns('col-md-4')
            ->hideOnIndex();

        yield DateTimeField::new('dueDate', 'Due Date')
            ->setHelp('Optional: deadline for the task')
            ->setColumns('col-md-4');

        yield DateTimeField::new('completedAt', 'Completed At')
            ->setHelp('Auto-set when status = Completed')
            ->hideOnForm()
            ->hideOnIndex()
            ->setColumns('col-md-4');

        // Flags
        yield BooleanField::new('isArchived', 'Archived')
            ->renderAsSwitch(false)
            ->setHelp('Hide from active lists')
            ->setColumns('col-md-3');

        yield BooleanField::new('isRecurringTemplate', 'Recurring Template')
            ->renderAsSwitch(false)
            ->setHelp('Marks task as template for recurring generation')
            ->hideOnIndex()
            ->setColumns('col-md-3');

        // Computed field: Subtask count (simplified for performance)
        if (Crud::PAGE_INDEX === $pageName) {
            yield IntegerField::new('subtaskCount', 'Subtasks')
                ->formatValue(function ($value, Task $task) {
                    $count = $task->getSubtasks()->count();

                    if ($count === 0) {
                        return '<span class="text-muted">-</span>';
                    }

                    return sprintf('<span class="badge badge-secondary">%d</span>', $count);
                })
                ->onlyOnIndex();
        }

        // Computed field: Overdue indicator
        if (Crud::PAGE_INDEX === $pageName) {
            yield BooleanField::new('isOverdue', 'Overdue')
                ->renderAsSwitch(false)
                ->formatValue(function ($value, Task $task) {
                    if ($task->isOverdue()) {
                        return '<span class="badge badge-danger"><i class="fa fa-exclamation-triangle"></i> Overdue</span>';
                    }
                    return '-';
                })
                ->onlyOnIndex();
        }

        // Collections - only on detail
        if (Crud::PAGE_DETAIL === $pageName) {
            yield CollectionField::new('subtasks', 'Subtasks')
                ->setTemplatePath('admin/field/task_subtasks.html.twig')
                ->onlyOnDetail();

            yield CollectionField::new('attachments', 'Attachments')
                ->setTemplatePath('admin/field/task_attachments.html.twig')
                ->onlyOnDetail();
        }

        // Recurrence info - only on detail
        if (Crud::PAGE_DETAIL === $pageName) {
            yield AssociationField::new('recurrenceRule', 'Recurrence Rule')
                ->setHelp('If this is a recurring template')
                ->onlyOnDetail();

            yield AssociationField::new('generatedFromRule', 'Generated From Rule')
                ->setHelp('If this task was auto-generated')
                ->onlyOnDetail();
        }

        // Timestamps
        yield DateTimeField::new('createdAt', 'Created At')
            ->hideOnForm()
            ->setColumns('col-md-4');

        yield DateTimeField::new('updatedAt', 'Updated At')
            ->hideOnForm()
            ->hideOnIndex()
            ->setColumns('col-md-4');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            // Entity filters
            ->add(EntityFilter::new('user', 'User'))
            ->add(EntityFilter::new('parentTask', 'Parent Task'))
            ->add(EntityFilter::new('tags', 'Tags'))

            // Choice filters for enums
            ->add(ChoiceFilter::new('status', 'Status')
                ->setChoices([
                    'Pending' => TaskStatus::PENDING->value,
                    'In Progress' => TaskStatus::IN_PROGRESS->value,
                    'Completed' => TaskStatus::COMPLETED->value,
                    'Cancelled' => TaskStatus::CANCELLED->value,
                ]))

            ->add(ChoiceFilter::new('priority', 'Priority')
                ->setChoices([
                    'Low' => TaskPriority::LOW->value,
                    'Medium' => TaskPriority::MEDIUM->value,
                    'High' => TaskPriority::HIGH->value,
                    'Urgent' => TaskPriority::URGENT->value,
                ]))

            // Boolean filters
            ->add(BooleanFilter::new('isArchived', 'Archived')
                ->setFormTypeOption('expanded', false))

            ->add(BooleanFilter::new('isRecurringTemplate', 'Recurring Template')
                ->setFormTypeOption('expanded', false))

            // Date filters
            ->add(DateTimeFilter::new('dueDate', 'Due Date'))
            ->add(DateTimeFilter::new('completedAt', 'Completed At'))
            ->add(DateTimeFilter::new('createdAt', 'Created At'));
    }

    public function configureActions(Actions $actions): Actions
    {
        // Custom action: Complete Task
        $completeAction = Action::new('complete', 'Complete')
            ->linkToCrudAction('completeTask')
            ->displayIf(static fn (Task $task) => !$task->isCompleted())
            ->setIcon('fa fa-check')
            ->setCssClass('btn btn-sm btn-success');

        // Custom action: Archive Task
        $archiveAction = Action::new('archive', 'Archive')
            ->linkToCrudAction('archiveTask')
            ->displayIf(static fn (Task $task) => !$task->isArchived())
            ->setIcon('fa fa-archive')
            ->setCssClass('btn btn-sm btn-warning');

        // Custom action: Unarchive Task
        $unarchiveAction = Action::new('unarchive', 'Unarchive')
            ->linkToCrudAction('unarchiveTask')
            ->displayIf(static fn (Task $task) => $task->isArchived())
            ->setIcon('fa fa-inbox')
            ->setCssClass('btn btn-sm btn-info');

        return $actions
            // Add view action to index page
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            // Add custom actions
            ->add(Crud::PAGE_INDEX, $completeAction)
            ->add(Crud::PAGE_INDEX, $archiveAction)
            ->add(Crud::PAGE_INDEX, $unarchiveAction)
            ->add(Crud::PAGE_DETAIL, $completeAction)
            ->add(Crud::PAGE_DETAIL, $archiveAction)
            ->add(Crud::PAGE_DETAIL, $unarchiveAction)

            // Customize default actions
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action
                ->setIcon('fa fa-plus')
                ->setLabel('Create Task')
                ->setCssClass('btn btn-primary')
            )
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action
                ->setIcon('fa fa-edit')
                ->setLabel(false)
            )
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action
                ->setIcon('fa fa-trash')
                ->setLabel(false)
            )
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action
                ->setIcon('fa fa-eye')
                ->setLabel(false)
            );
    }

    /**
     * Custom action: Complete task
     */
    public function completeTask(AdminContext $context): Response
    {
        /** @var Task $task */
        $task = $context->getEntity()->getInstance();

        $task->setStatus(TaskStatus::COMPLETED);
        // completedAt is auto-set in Task::setStatus()

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Task "%s" marked as completed!', $task->getTitle()));

        return $this->redirect($context->getReferrer());
    }

    /**
     * Custom action: Archive task
     */
    public function archiveTask(AdminContext $context): Response
    {
        /** @var Task $task */
        $task = $context->getEntity()->getInstance();

        $task->setIsArchived(true);

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Task "%s" archived!', $task->getTitle()));

        return $this->redirect($context->getReferrer());
    }

    /**
     * Custom action: Unarchive task
     */
    public function unarchiveTask(AdminContext $context): Response
    {
        /** @var Task $task */
        $task = $context->getEntity()->getInstance();

        $task->setIsArchived(false);

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Task "%s" unarchived!', $task->getTitle()));

        return $this->redirect($context->getReferrer());
    }

    /**
     * Optimize query with eager loading to prevent N+1 problems
     * IMPORTANT: Only eager load what's actually displayed on INDEX page!
     */
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // ONLY eager load user (displayed on INDEX)
        // Tags and subtasks are now shown as COUNT only, so no eager loading needed!
        $qb->leftJoin('entity.user', 'u')
           ->addSelect('u');

        return $qb;
    }

    /**
     * Validate task data before persisting
     */
    public function persistEntity($entityManager, $entityInstance): void
    {
        /** @var Task $task */
        $task = $entityInstance;

        // Validation: startDate must be before dueDate
        if ($task->getStartDate() && $task->getDueDate()) {
            if ($task->getStartDate() > $task->getDueDate()) {
                $this->addFlash('error', 'Start date cannot be after due date!');
                throw new \RuntimeException('Invalid dates: startDate > dueDate');
            }
        }

        // Validation: Task cannot be its own parent
        if ($task->getParentTask() && $task->getParentTask()->getId() === $task->getId()) {
            $this->addFlash('error', 'Task cannot be its own parent!');
            throw new \RuntimeException('Invalid parent: circular reference');
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Validate task data before updating
     */
    public function updateEntity($entityManager, $entityInstance): void
    {
        /** @var Task $task */
        $task = $entityInstance;

        // Same validation as persistEntity
        if ($task->getStartDate() && $task->getDueDate()) {
            if ($task->getStartDate() > $task->getDueDate()) {
                $this->addFlash('error', 'Start date cannot be after due date!');
                throw new \RuntimeException('Invalid dates: startDate > dueDate');
            }
        }

        if ($task->getParentTask() && $task->getParentTask()->getId() === $task->getId()) {
            $this->addFlash('error', 'Task cannot be its own parent!');
            throw new \RuntimeException('Invalid parent: circular reference');
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}
