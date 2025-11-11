<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\RecurrenceRule;
use App\Entity\Task;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class RecurrenceRuleCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return RecurrenceRule::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Recurrence Rule')
            ->setEntityLabelInPlural('Recurrence Rules')
            ->setPageTitle('index', 'Recurrence Rules Management')
            ->setPageTitle('new', 'Create new Recurrence Rule')
            ->setPageTitle('edit', fn (RecurrenceRule $rule) => sprintf(
                'Edit rule: %s (%s)',
                $rule->getTemplateTask()->getTitle(),
                ucfirst($rule->getRecurrenceType()),
            ))
            ->setPageTitle('detail', fn (RecurrenceRule $rule) => sprintf(
                'Recurrence Rule: %s',
                $rule->getTemplateTask()->getTitle(),
            ))

            // Pagination - reduced for performance
            ->setPaginatorPageSize(15)
            ->setPaginatorRangeSize(3)

            // Search
            ->setSearchFields(['recurrenceType', 'templateTask.title', 'createdBy.email'])

            // Default sort: active first, then by next occurrence
            ->setDefaultSort(['isActive' => 'DESC', 'nextOccurrenceDate' => 'ASC'])

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

        // Template Task - required association
        yield AssociationField::new('templateTask', 'Template Task')
            ->setRequired(true)
            ->autocomplete()
            ->setCrudController(TaskCrudController::class)
            ->formatValue(function ($value, RecurrenceRule $rule) {
                $task = $rule->getTemplateTask();

                return sprintf(
                    '<strong>%s</strong> <small class="text-muted">(#%d)</small>',
                    htmlspecialchars($task->getTitle()),
                    $task->getId(),
                );
            })
            ->setHelp('The task that will be used as template for recurring tasks')
            ->setColumns('col-md-6');

        // Created By
        yield AssociationField::new('createdBy', 'Created By')
            ->setRequired(true)
            ->autocomplete()
            ->setCrudController(UserCrudController::class)
            ->formatValue(function ($value, RecurrenceRule $rule) {
                return $rule->getCreatedBy()->getEmail();
            })
            ->setColumns('col-md-6');

        // Recurrence Type - choice field with all types
        $recurrenceTypes = [
            'Daily'   => RecurrenceRule::TYPE_DAILY,
            'Weekly'  => RecurrenceRule::TYPE_WEEKLY,
            'Monthly' => RecurrenceRule::TYPE_MONTHLY,
            'Yearly'  => RecurrenceRule::TYPE_YEARLY,
            'Custom'  => RecurrenceRule::TYPE_CUSTOM,
        ];

        yield ChoiceField::new('recurrenceType', 'Recurrence Type')
            ->setRequired(true)
            ->setChoices($recurrenceTypes)
            ->formatValue(function ($value, RecurrenceRule $rule) {
                $typeColors = [
                    RecurrenceRule::TYPE_DAILY   => 'info',
                    RecurrenceRule::TYPE_WEEKLY  => 'success',
                    RecurrenceRule::TYPE_MONTHLY => 'warning',
                    RecurrenceRule::TYPE_YEARLY  => 'danger',
                    RecurrenceRule::TYPE_CUSTOM  => 'secondary',
                ];

                $color = $typeColors[$rule->getRecurrenceType()] ?? 'secondary';

                return sprintf(
                    '<span class="badge badge-%s">%s</span>',
                    $color,
                    strtoupper($rule->getRecurrenceType()),
                );
            })
            ->setHelp('Type of recurrence pattern')
            ->setColumns('col-md-4');

        // Interval - for custom type (every N days)
        yield IntegerField::new('interval', 'Interval (days)')
            ->setHelp('For custom type: repeat every N days')
            ->hideOnIndex()
            ->setColumns('col-md-4');

        // Days of Week - for weekly type (JSON array)
        // IMPORTANT: Use virtual field name to avoid TextConfigurator trying to convert JSON array
        // Cannot use Field::new('daysOfWeek') because it maps to entity property (JSON type)
        yield Field::new('daysOfWeekDisplay', 'Days of Week')
            ->formatValue(function ($value, RecurrenceRule $rule) {
                $days = $rule->getDaysOfWeek();

                if (!$days || empty($days)) {
                    return '<span class="badge badge-secondary">Not set</span>';
                }

                $dayNames = [
                    1 => 'Mon', 2 => 'Tue', 3 => 'Wed',
                    4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun',
                ];

                $formatted = array_map(fn ($d) => $dayNames[$d] ?? $d, $days);

                return sprintf(
                    '<span class="badge badge-success">%s</span>',
                    implode(', ', $formatted),
                );
            })
            ->setHelp('For weekly type: [1,2,3,4,5] = Mon-Fri. Edit via API or directly in database.')
            ->hideOnForm(); // Virtual field - hide on NEW/EDIT forms

        // Day of Month - for monthly type
        yield IntegerField::new('dayOfMonth', 'Day of Month')
            ->setHelp('For monthly type: day of month (1-31)')
            ->hideOnIndex()
            ->setColumns('col-md-4');

        // Month of Year - for yearly type
        yield IntegerField::new('monthOfYear', 'Month of Year')
            ->setHelp('For yearly type: month number (1-12)')
            ->hideOnIndex()
            ->setColumns('col-md-4');

        // Time of Day - when to create task
        yield TimeField::new('timeOfDay', 'Time of Day')
            ->setHelp('Time when task should be created (e.g., 09:00)')
            ->hideOnIndex()
            ->setColumns('col-md-4');

        // Next Occurrence Date - when next task will be created
        yield DateTimeField::new('nextOccurrenceDate', 'Next Occurrence')
            ->setColumns('col-md-4');

        // End Date - when to stop
        yield DateField::new('endDate', 'End Date')
            ->setHelp('Optional: date when recurrence should stop')
            ->hideOnIndex()
            ->setColumns('col-md-6');

        // Max Occurrences
        yield IntegerField::new('maxOccurrences', 'Max Occurrences')
            ->setHelp('Optional: maximum number of tasks to create')
            ->hideOnIndex()
            ->setColumns('col-md-6');

        // Current Occurrences / Max Occurrences - progress display
        yield Field::new('occurrencesProgress', 'Progress')
            ->formatValue(function ($value, RecurrenceRule $rule) {
                $current = $rule->getCurrentOccurrences();
                $max = $rule->getMaxOccurrences();

                if (!$max) {
                    return sprintf(
                        '<span class="badge badge-info">%d created</span> <small>(unlimited)</small>',
                        $current,
                    );
                }

                $percentage = $max > 0 ? min(100, ($current / $max) * 100) : 0;
                $percentageRounded = round($percentage);

                $progressClass = $percentage >= 100 ? 'success' : ($percentage >= 75 ? 'warning' : 'info');

                return sprintf(
                    '<div class="progress" style="height: 20px; min-width: 150px;">
                        <div class="progress-bar bg-%s" role="progressbar" style="width: %d%%">
                            %d / %d
                        </div>
                    </div>',
                    $progressClass,
                    $percentageRounded,
                    $current,
                    $max,
                );
            })
            ->onlyOnDetail();

        // Current Occurrences (simple display for index)
        yield IntegerField::new('currentOccurrences', 'Created')
            ->formatValue(function ($value, RecurrenceRule $rule) {
                $current = $rule->getCurrentOccurrences();
                $max = $rule->getMaxOccurrences();

                if (!$max) {
                    return sprintf('<span class="badge badge-info">%d</span>', $current);
                }

                return sprintf(
                    '<span class="badge badge-%s">%d / %d</span>',
                    $current >= $max ? 'success' : 'info',
                    $current,
                    $max,
                );
            })
            ->hideOnForm()
            ->hideOnDetail();

        // Is Active - with badge
        yield BooleanField::new('isActive', 'Active')
            ->renderAsSwitch(Crud::PAGE_EDIT === $pageName || Crud::PAGE_NEW === $pageName)
            ->formatValue(function ($value, RecurrenceRule $rule) {
                // Check if should stop
                $shouldStop = $rule->shouldStopRecurrence();

                if ($shouldStop) {
                    return '<span class="badge badge-danger">
                        <i class="fa fa-ban"></i> Expired
                    </span>';
                }

                if ($rule->isActive()) {
                    return '<span class="badge badge-success">
                        <i class="fa fa-check-circle"></i> Active
                    </span>';
                }

                return '<span class="badge badge-secondary">
                    <i class="fa fa-pause-circle"></i> Paused
                </span>';
            });

        // Timestamps
        yield DateTimeField::new('createdAt', 'Created At')
            ->hideOnForm()
            ->hideOnIndex()
            ->setColumns('col-md-6');

        yield DateTimeField::new('updatedAt', 'Updated At')
            ->hideOnForm()
            ->hideOnIndex()
            ->setColumns('col-md-6');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            // Recurrence type filter
            ->add(ChoiceFilter::new('recurrenceType', 'Type')->setChoices([
                'Daily'   => RecurrenceRule::TYPE_DAILY,
                'Weekly'  => RecurrenceRule::TYPE_WEEKLY,
                'Monthly' => RecurrenceRule::TYPE_MONTHLY,
                'Yearly'  => RecurrenceRule::TYPE_YEARLY,
                'Custom'  => RecurrenceRule::TYPE_CUSTOM,
            ]))

            // Active status filter
            ->add(BooleanFilter::new('isActive', 'Active'))

            // Entity filter - only for createdBy (small table - 25 users)
            // REMOVED templateTask filter - causes N+1 on 213K tasks!
            ->add(EntityFilter::new('createdBy', 'Created By'));
    }

    public function configureActions(Actions $actions): Actions
    {
        // Custom action: Activate Rule
        $activateAction = Action::new('activate', 'Activate')
            ->linkToCrudAction('activateRule')
            ->setIcon('fa fa-play-circle')
            ->setCssClass('btn btn-success')
            ->displayIf(fn (RecurrenceRule $rule) => !$rule->isActive() && !$rule->shouldStopRecurrence());

        // Custom action: Deactivate Rule
        $deactivateAction = Action::new('deactivate', 'Pause')
            ->linkToCrudAction('deactivateRule')
            ->setIcon('fa fa-pause-circle')
            ->setCssClass('btn btn-warning')
            ->displayIf(fn (RecurrenceRule $rule) => $rule->isActive());

        return $actions
            // Add custom actions
            ->add(Crud::PAGE_INDEX, $activateAction)
            ->add(Crud::PAGE_INDEX, $deactivateAction)
            ->add(Crud::PAGE_DETAIL, $activateAction)
            ->add(Crud::PAGE_DETAIL, $deactivateAction)

            // Add view action to index page
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            // Customize default actions
            ->update(
                Crud::PAGE_INDEX,
                Action::NEW,
                fn (Action $action) => $action
                    ->setIcon('fa fa-plus')
                    ->setLabel('Create Rule')
                    ->setCssClass('btn btn-primary'),
            )
            ->update(
                Crud::PAGE_INDEX,
                Action::EDIT,
                fn (Action $action) => $action
                    ->setIcon('fa fa-edit')
                    ->setLabel(false),
            )
            ->update(
                Crud::PAGE_INDEX,
                Action::DELETE,
                fn (Action $action) => $action
                    ->setIcon('fa fa-trash')
                    ->setLabel(false),
            )
            ->update(
                Crud::PAGE_INDEX,
                Action::DETAIL,
                fn (Action $action) => $action
                    ->setIcon('fa fa-eye')
                    ->setLabel(false),
            );
    }

    /**
     * Custom action: Activate recurrence rule
     */
    public function activateRule(AdminContext $context): Response
    {
        /** @var RecurrenceRule $rule */
        $rule = $context->getEntity()->getInstance();

        if ($rule->shouldStopRecurrence()) {
            $this->addFlash('error', 'Cannot activate rule: it has expired (reached end date or max occurrences)');
        } else {
            $rule->setIsActive(true);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'Recurrence rule for "%s" activated!',
                $rule->getTemplateTask()->getTitle(),
            ));
        }

        return $this->redirect($context->getReferrer());
    }

    /**
     * Custom action: Deactivate recurrence rule
     */
    public function deactivateRule(AdminContext $context): Response
    {
        /** @var RecurrenceRule $rule */
        $rule = $context->getEntity()->getInstance();

        $rule->setIsActive(false);
        $this->entityManager->flush();

        $this->addFlash('warning', sprintf(
            'Recurrence rule for "%s" paused. No new tasks will be created.',
            $rule->getTemplateTask()->getTitle(),
        ));

        return $this->redirect($context->getReferrer());
    }

    /**
     * Optimize query with eager loading to prevent N+1 problems
     */
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters,
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // Eager load templateTask and createdBy to avoid N+1 queries
        $qb->leftJoin('entity.templateTask', 't')
            ->addSelect('t')
            ->leftJoin('entity.createdBy', 'u')
            ->addSelect('u');

        return $qb;
    }

    /**
     * Validate recurrence rule data before persisting
     *
     * @param mixed $entityManager
     * @param mixed $entityInstance
     */
    public function persistEntity($entityManager, $entityInstance): void
    {
        /** @var RecurrenceRule $rule */
        $rule = $entityInstance;

        // Validation based on recurrence type
        $this->validateRecurrenceRule($rule);

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Validate recurrence rule data before updating
     *
     * @param mixed $entityManager
     * @param mixed $entityInstance
     */
    public function updateEntity($entityManager, $entityInstance): void
    {
        /** @var RecurrenceRule $rule */
        $rule = $entityInstance;

        // Validation based on recurrence type
        $this->validateRecurrenceRule($rule);

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Show info when deleting rule
     *
     * @param mixed $entityManager
     * @param mixed $entityInstance
     */
    public function deleteEntity($entityManager, $entityInstance): void
    {
        /** @var RecurrenceRule $rule */
        $rule = $entityInstance;

        $taskTitle = $rule->getTemplateTask()->getTitle();
        $createdCount = $rule->getCurrentOccurrences();

        $this->addFlash('success', sprintf(
            'Recurrence rule for "%s" deleted. %d task(s) were created from this rule.',
            $taskTitle,
            $createdCount,
        ));

        parent::deleteEntity($entityManager, $entityInstance);
    }

    /**
     * Validate rule based on recurrence type
     */
    private function validateRecurrenceRule(RecurrenceRule $rule): void
    {
        $type = $rule->getRecurrenceType();

        switch ($type) {
            case RecurrenceRule::TYPE_CUSTOM:
                if (!$rule->getInterval() || $rule->getInterval() < 1) {
                    $this->addFlash('error', 'Custom type requires interval (number of days) to be set!');

                    throw new RuntimeException('Custom recurrence requires interval');
                }
                break;

            case RecurrenceRule::TYPE_WEEKLY:
                if (!$rule->getDaysOfWeek() || empty($rule->getDaysOfWeek())) {
                    $this->addFlash('error', 'Weekly type requires days of week to be set!');

                    throw new RuntimeException('Weekly recurrence requires daysOfWeek');
                }
                break;

            case RecurrenceRule::TYPE_MONTHLY:
                if (!$rule->getDayOfMonth() || $rule->getDayOfMonth() < 1 || $rule->getDayOfMonth() > 31) {
                    $this->addFlash('error', 'Monthly type requires day of month (1-31) to be set!');

                    throw new RuntimeException('Monthly recurrence requires dayOfMonth');
                }
                break;

            case RecurrenceRule::TYPE_YEARLY:
                if (!$rule->getMonthOfYear() || $rule->getMonthOfYear() < 1 || $rule->getMonthOfYear() > 12) {
                    $this->addFlash('error', 'Yearly type requires month of year (1-12) to be set!');

                    throw new RuntimeException('Yearly recurrence requires monthOfYear');
                }

                if (!$rule->getDayOfMonth() || $rule->getDayOfMonth() < 1 || $rule->getDayOfMonth() > 31) {
                    $this->addFlash('error', 'Yearly type requires day of month (1-31) to be set!');

                    throw new RuntimeException('Yearly recurrence requires dayOfMonth');
                }
                break;
        }

        // Validate end conditions
        if ($rule->getEndDate() && $rule->getMaxOccurrences()) {
            $this->addFlash('warning', 'Both end date and max occurrences are set. Rule will stop at whichever comes first.');
        }

        if (!$rule->getEndDate() && !$rule->getMaxOccurrences()) {
            $this->addFlash('info', 'No end date or max occurrences set. Rule will run indefinitely until manually stopped.');
        }
    }
}
