<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use App\Entity\Task;
use App\Enum\TaskStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users')
            ->setPageTitle('index', 'User Management')
            ->setPageTitle('new', 'Create new User')
            ->setPageTitle('edit', fn (User $user) => sprintf('Edit user: %s', $user->getEmail()))
            ->setPageTitle('detail', fn (User $user) => sprintf('User: %s', $user->getEmail()))
            
            // Pagination - optimized for performance
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(3)

            // Search - only by email
            ->setSearchFields(['email'])
            
            // Default sort
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

        // Email field - required, searchable
        yield EmailField::new('email', 'Email')
            ->setRequired(true)
            ->setHelp('User email address (unique)')
            ->setColumns('col-md-6');

        // Password field - only on forms
        if (Crud::PAGE_NEW === $pageName || Crud::PAGE_EDIT === $pageName) {
            yield TextField::new('plainPassword', 'Password')
                ->setFormType(PasswordType::class)
                ->setRequired(Crud::PAGE_NEW === $pageName)
                ->setHelp(
                    Crud::PAGE_NEW === $pageName
                        ? 'Minimum 8 characters'
                        : 'Leave blank if you don\'t want to change the password'
                )
                ->setColumns('col-md-6')
                ->onlyOnForms();
        }

        // === STATISTICS SECTION (INDEX) ===

        // Total Tasks
        yield IntegerField::new('totalTasks', 'Total Tasks')
            ->formatValue(function ($value, User $user) {
                try {
                    // Use direct query to avoid lazy loading issues
                    $count = $this->entityManager->createQueryBuilder()
                        ->select('COUNT(t.id)')
                        ->from('App\Entity\Task', 't')
                        ->where('t.user = :user')
                        ->setParameter('user', $user)
                        ->getQuery()
                        ->getSingleScalarResult();

                    if ($count == 0) {
                        return '<span class="text-muted">-</span>';
                    }
                    return sprintf('<span class="badge badge-primary">%d</span>', $count);
                } catch (\Exception $e) {
                    return '<span class="text-danger">Error</span>';
                }
            })
            ->onlyOnIndex();

        // Completed Tasks
        yield IntegerField::new('completedTasks', 'Completed')
            ->formatValue(function ($value, User $user) {
                try {
                    // Use direct query to avoid lazy loading issues
                    $count = $this->entityManager->createQueryBuilder()
                        ->select('COUNT(t.id)')
                        ->from('App\Entity\Task', 't')
                        ->where('t.user = :user')
                        ->andWhere('t.status = :status')
                        ->setParameter('user', $user)
                        ->setParameter('status', TaskStatus::COMPLETED)
                        ->getQuery()
                        ->getSingleScalarResult();

                    if ($count == 0) {
                        return '<span class="text-muted">-</span>';
                    }
                    return sprintf('<span class="badge badge-success">%d</span>', $count);
                } catch (\Exception $e) {
                    return '<span class="text-danger">Error</span>';
                }
            })
            ->onlyOnIndex();

        // Active Tasks (not completed, not cancelled)
        yield IntegerField::new('activeTasks', 'Active')
            ->formatValue(function ($value, User $user) {
                try {
                    // Use direct query to avoid lazy loading issues
                    $count = $this->entityManager->createQueryBuilder()
                        ->select('COUNT(t.id)')
                        ->from('App\Entity\Task', 't')
                        ->where('t.user = :user')
                        ->andWhere('t.status NOT IN (:excludedStatuses)')
                        ->setParameter('user', $user)
                        ->setParameter('excludedStatuses', [TaskStatus::COMPLETED, TaskStatus::CANCELLED])
                        ->getQuery()
                        ->getSingleScalarResult();

                    if ($count == 0) {
                        return '<span class="text-muted">-</span>';
                    }

                    $badgeClass = $count > 10 ? 'warning' : 'info';
                    return sprintf('<span class="badge badge-%s">%d</span>', $badgeClass, $count);
                } catch (\Exception $e) {
                    return '<span class="text-danger">Error</span>';
                }
            })
            ->onlyOnIndex();

        // Total Tags
        yield IntegerField::new('totalTags', 'Tags')
            ->formatValue(function ($value, User $user) {
                try {
                    // Get tags count via query to avoid loading all tags
                    $count = $this->entityManager->createQueryBuilder()
                        ->select('COUNT(tag.id)')
                        ->from('App\Entity\Tag', 'tag')
                        ->where('tag.user = :user')
                        ->setParameter('user', $user)
                        ->getQuery()
                        ->getSingleScalarResult();

                    if ($count == 0) {
                        return '<span class="text-muted">-</span>';
                    }

                    return sprintf('<span class="badge badge-info">%d</span>', $count);
                } catch (\Exception $e) {
                    return '<span class="text-danger">Error</span>';
                }
            })
            ->onlyOnIndex();

        // === GOOGLE AUTH (DETAIL ONLY) ===

        // Google fields - moved to detail page only
        if (Crud::PAGE_DETAIL === $pageName) {
            yield TextField::new('googleUserName', 'Google Name')
                ->setHelp('Name from Google account')
                ->onlyOnDetail()
                ->setColumns('col-md-6');

            yield TextField::new('googleId', 'Google ID')
                ->setHelp('Unique Google user ID')
                ->onlyOnDetail()
                ->setColumns('col-md-6');

            yield BooleanField::new('hasGoogleAuth', 'Google Authentication')
                ->renderAsSwitch(false)
                ->setHelp('Authenticated via Google')
                ->onlyOnDetail();
        }

        // Roles field - detail/edit only
        yield ArrayField::new('roles', 'Roles')
            ->setHelp('User roles')
            ->hideOnIndex()
            ->setColumns('col-md-6');

        // === DETAILED STATISTICS (DETAIL PAGE) ===

        if (Crud::PAGE_DETAIL === $pageName) {
            // Task Statistics Summary
            yield Field::new('taskStatistics', 'Task Statistics')
                ->formatValue(function ($value, User $user) {
                    $tasks = $user->getTasks();
                    $total = $tasks->count();

                    if ($total === 0) {
                        return '<div class="alert alert-info">No tasks yet</div>';
                    }

                    $completed = 0;
                    $inProgress = 0;
                    $pending = 0;
                    $cancelled = 0;
                    $archived = 0;

                    foreach ($tasks as $task) {
                        match ($task->getStatus()) {
                            TaskStatus::COMPLETED => $completed++,
                            TaskStatus::IN_PROGRESS => $inProgress++,
                            TaskStatus::PENDING => $pending++,
                            TaskStatus::CANCELLED => $cancelled++,
                            default => null,
                        };

                        if ($task->isArchived()) {
                            $archived++;
                        }
                    }

                    $completionRate = $total > 0 ? round(($completed / $total) * 100) : 0;

                    return sprintf('
                        <div class="row">
                            <div class="col-md-12">
                                <h6>Status Breakdown:</h6>
                                <div class="mb-3">
                                    <span class="badge badge-success mr-2">Completed: %d</span>
                                    <span class="badge badge-primary mr-2">In Progress: %d</span>
                                    <span class="badge badge-secondary mr-2">Pending: %d</span>
                                    <span class="badge badge-danger mr-2">Cancelled: %d</span>
                                    <span class="badge badge-warning mr-2">Archived: %d</span>
                                </div>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-success" style="width: %d%%">
                                        Completion: %d%%
                                    </div>
                                </div>
                            </div>
                        </div>
                    ', $completed, $inProgress, $pending, $cancelled, $archived, $completionRate, $completionRate);
                })
                ->onlyOnDetail();

            // Recent Tasks (last 5)
            yield Field::new('recentTasks', 'Recent Tasks (Last 5)')
                ->formatValue(function ($value, User $user) {
                    $recentTasks = $this->entityManager->createQueryBuilder()
                        ->select('t')
                        ->from('App\Entity\Task', 't')
                        ->where('t.user = :user')
                        ->setParameter('user', $user)
                        ->orderBy('t.createdAt', 'DESC')
                        ->setMaxResults(5)
                        ->getQuery()
                        ->getResult();

                    if (empty($recentTasks)) {
                        return '<span class="badge badge-secondary">No recent tasks</span>';
                    }

                    $html = '<ul class="list-group list-group-flush">';

                    foreach ($recentTasks as $task) {
                        $statusBadge = match($task->getStatus()) {
                            TaskStatus::COMPLETED => 'success',
                            TaskStatus::IN_PROGRESS => 'primary',
                            TaskStatus::PENDING => 'secondary',
                            TaskStatus::CANCELLED => 'danger',
                            default => 'secondary',
                        };

                        $html .= sprintf(
                            '<li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span><strong>%s</strong></span>
                                    <span class="badge badge-%s">%s</span>
                                </div>
                                <small class="text-muted">Created: %s</small>
                            </li>',
                            htmlspecialchars($task->getTitle()),
                            $statusBadge,
                            $task->getStatus()->value,
                            $task->getCreatedAt()->format('d.m.Y H:i')
                        );
                    }

                    $html .= '</ul>';
                    return $html;
                })
                ->onlyOnDetail();

            // User Tags List
            yield Field::new('userTags', 'User Tags')
                ->formatValue(function ($value, User $user) {
                    $tags = $this->entityManager->createQueryBuilder()
                        ->select('tag')
                        ->from('App\Entity\Tag', 'tag')
                        ->where('tag.user = :user')
                        ->setParameter('user', $user)
                        ->orderBy('tag.usageCount', 'DESC')
                        ->setMaxResults(10)
                        ->getQuery()
                        ->getResult();

                    if (empty($tags)) {
                        return '<span class="badge badge-secondary">No tags</span>';
                    }

                    $html = '<div class="mb-2">';

                    foreach ($tags as $tag) {
                        $html .= sprintf(
                            '<span class="badge mr-2 mb-2" style="background-color: %s; color: white;">
                                %s <small>(%d)</small>
                            </span>',
                            htmlspecialchars($tag->getColor()),
                            htmlspecialchars($tag->getName()),
                            $tag->getUsageCount()
                        );
                    }

                    $html .= '</div>';
                    return $html;
                })
                ->onlyOnDetail();
        }

        // Timestamps
        yield DateTimeField::new('createdAt', 'Created At')
            ->hideOnForm()
            ->setColumns('col-md-6');

        yield DateTimeField::new('updatedAt', 'Updated At')
            ->hideOnForm()
            ->hideOnIndex()
            ->setColumns('col-md-6');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            // Text filter
            ->add(TextFilter::new('email', 'Email'))

            // Date filters
            ->add(DateTimeFilter::new('createdAt', 'Created At'))
            ->add(DateTimeFilter::new('updatedAt', 'Updated At'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Add view action to index page
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            // Customize labels and icons for better UX
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action
                ->setIcon('fa fa-plus')
                ->setLabel('Create User')
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
     * No need for eager loading - statistics use direct DQL queries
     */
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        // No eager loading needed - all statistics use direct queries
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
    }

    /**
     * Hash password before persisting user
     */
    public function persistEntity($entityManager, $entityInstance): void
    {
        /** @var User $entityInstance */
        if ($entityInstance instanceof User && $entityInstance->getPlainPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $entityInstance,
                $entityInstance->getPlainPassword()
            );
            $entityInstance->setPassword($hashedPassword);
            $entityInstance->eraseCredentials();
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Hash password before updating user
     */
    public function updateEntity($entityManager, $entityInstance): void
    {
        /** @var User $entityInstance */
        if ($entityInstance instanceof User && $entityInstance->getPlainPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $entityInstance,
                $entityInstance->getPlainPassword()
            );
            $entityInstance->setPassword($hashedPassword);
            $entityInstance->eraseCredentials();
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}

