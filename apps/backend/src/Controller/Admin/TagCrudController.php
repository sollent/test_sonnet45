<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Tag;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use RuntimeException;

class TagCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Tag::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Tag')
            ->setEntityLabelInPlural('Tags')
            ->setPageTitle('index', 'Tag Management')
            ->setPageTitle('new', 'Create new Tag')
            ->setPageTitle('edit', fn (Tag $tag) => sprintf('Edit tag: %s', $tag->getName()))
            ->setPageTitle('detail', fn (Tag $tag) => sprintf('Tag: %s', $tag->getName()))

            // Pagination - reduced for performance
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(3)

            // Search
            ->setSearchFields(['name', 'icon', 'user.email'])

            // Default sort: most used first
            ->setDefaultSort(['usageCount' => 'DESC'])

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

        // User (owner) - required association
        yield AssociationField::new('user', 'User')
            ->setRequired(true)
            ->autocomplete()
            ->setCrudController(UserCrudController::class)
            ->formatValue(function ($value, Tag $tag) {
                return $tag->getUser() ? $tag->getUser()->getEmail() : '-';
            })
            ->setHelp('Tag owner')
            ->setColumns('col-md-4');

        // Name - required, unique per user
        yield TextField::new('name', 'Name')
            ->setRequired(true)
            ->setMaxLength(Crud::PAGE_INDEX === $pageName ? 30 : 50)
            ->setHelp('Tag name (max 50 characters, unique per user)')
            ->setColumns('col-md-4');

        // Color - show as color dot with hex code on index, ColorField on forms
        if (Crud::PAGE_INDEX === $pageName) {
            yield TextField::new('color', 'Color')
                ->renderAsHtml()
                ->formatValue(function ($value, Tag $tag) {
                    return sprintf(
                        '<div style="display: flex; align-items: center; gap: 8px;">
                            <span style="
                                display: inline-block;
                                width: 24px;
                                height: 24px;
                                border-radius: 50%%;
                                background-color: %s;
                                border: 2px solid #e5e7eb;
                            "></span>
                            <code style="
                                font-family: monospace;
                                font-size: 0.875rem;
                                color: #6b7280;
                            ">%s</code>
                        </div>',
                        $tag->getColor(),
                        strtoupper($tag->getColor()),
                    );
                })
                ->setColumns('col-md-4');
        } else {
            yield ColorField::new('color', 'Color')
                ->setRequired(true)
                ->setHelp('Tag color in hex format (#RRGGBB)')
                ->setColumns('col-md-4');
        }

        // Icon - optional
        if (Crud::PAGE_INDEX !== $pageName) {
            yield TextField::new('icon', 'Icon')
                ->setHelp('Optional: icon name (e.g., "briefcase", "home")')
                ->hideOnIndex()
                ->setColumns('col-md-6');
        }

        // Usage Count - readonly, formatted
        yield IntegerField::new('usageCount', 'Usage')
            ->formatValue(function ($value, Tag $tag) {
                $count = $tag->getUsageCount();

                if ($count === 0) {
                    return '<span class="badge badge-secondary">Unused</span>';
                }

                $badgeClass = $count >= 10 ? 'success' : ($count >= 5 ? 'info' : 'light');

                return sprintf(
                    '<span class="badge badge-%s">
                        <i class="fa fa-tasks"></i> %d task%s
                    </span>',
                    $badgeClass,
                    $count,
                    $count !== 1 ? 's' : '',
                );
            })
            ->setHelp('Number of tasks using this tag')
            ->hideOnForm();

        // Associated tasks - only on detail (simplified for performance)
        // IMPORTANT: Use CollectionField, not Field, for collections (Doctrine type 8)
        if (Crud::PAGE_DETAIL === $pageName) {
            yield CollectionField::new('tasks', 'Associated Tasks')
                ->formatValue(function ($value, Tag $tag) {
                    $tasks = $tag->getTasks();

                    if ($tasks->isEmpty()) {
                        return '<span class="badge badge-secondary">No tasks</span>';
                    }

                    $html = '<ul class="list-group list-group-flush">';

                    $displayLimit = 5; // Reduced from 10 to 5 for performance
                    $count = 0;

                    foreach ($tasks as $task) {
                        if ($count >= $displayLimit) {
                            $remaining = $tasks->count() - $displayLimit;
                            $html .= sprintf(
                                '<li class="list-group-item"><em>... and %d more task%s</em></li>',
                                $remaining,
                                $remaining !== 1 ? 's' : '',
                            );
                            break;
                        }

                        $statusBadge = $task->isCompleted() ? 'success' : 'secondary';
                        $statusIcon = $task->isCompleted() ? 'check-circle' : 'circle-o';

                        // Simplified without generateUrl (just show task info)
                        $html .= sprintf(
                            '<li class="list-group-item">
                                <i class="fa fa-%s text-%s"></i> <strong>%s</strong> <small class="text-muted">(#%d)</small>
                            </li>',
                            $statusIcon,
                            $statusBadge,
                            htmlspecialchars($task->getTitle()),
                            $task->getId(),
                        );

                        $count++;
                    }

                    $html .= '</ul>';

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
            // Entity filter
            ->add(EntityFilter::new('user', 'User'))

            // Text filters
            ->add(TextFilter::new('name', 'Name'))
            ->add(TextFilter::new('icon', 'Icon'))

            // Numeric filter for usage
            ->add(NumericFilter::new('usageCount', 'Usage Count'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Add view action to index page
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            // Customize default actions
            ->update(
                Crud::PAGE_INDEX,
                Action::NEW,
                fn (Action $action) => $action
                    ->setIcon('fa fa-plus')
                    ->setLabel('Create Tag')
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
     * Optimize query with eager loading to prevent N+1 problems
     */
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters,
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // Eager load user to avoid N+1 queries
        $qb->leftJoin('entity.user', 'u')
            ->addSelect('u');

        return $qb;
    }

    /**
     * Validate tag data before persisting
     *
     * @param mixed $entityManager
     * @param mixed $entityInstance
     */
    public function persistEntity($entityManager, $entityInstance): void
    {
        /** @var Tag $tag */
        $tag = $entityInstance;

        // Validation: name + user must be unique
        $existingTag = $this->entityManager->getRepository(Tag::class)->findOneBy([
            'name' => $tag->getName(),
            'user' => $tag->getUser(),
        ]);

        if ($existingTag && $existingTag->getId() !== $tag->getId()) {
            $this->addFlash('error', sprintf(
                'Tag "%s" already exists for user %s!',
                $tag->getName(),
                $tag->getUser()->getEmail(),
            ));

            throw new RuntimeException('Duplicate tag name for user');
        }

        // Validation: color format
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $tag->getColor())) {
            $this->addFlash('error', 'Invalid color format! Use hex format (#RRGGBB)');

            throw new RuntimeException('Invalid color format');
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Validate tag data before updating
     *
     * @param mixed $entityManager
     * @param mixed $entityInstance
     */
    public function updateEntity($entityManager, $entityInstance): void
    {
        /** @var Tag $tag */
        $tag = $entityInstance;

        // Same validation as persistEntity
        $existingTag = $this->entityManager->getRepository(Tag::class)->findOneBy([
            'name' => $tag->getName(),
            'user' => $tag->getUser(),
        ]);

        if ($existingTag && $existingTag->getId() !== $tag->getId()) {
            $this->addFlash('error', sprintf(
                'Tag "%s" already exists for user %s!',
                $tag->getName(),
                $tag->getUser()->getEmail(),
            ));

            throw new RuntimeException('Duplicate tag name for user');
        }

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $tag->getColor())) {
            $this->addFlash('error', 'Invalid color format! Use hex format (#RRGGBB)');

            throw new RuntimeException('Invalid color format');
        }

        // Update usage count before saving
        $tag->updateUsageCount();

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Show warning before deleting tag with tasks
     *
     * @param mixed $entityManager
     * @param mixed $entityInstance
     */
    public function deleteEntity($entityManager, $entityInstance): void
    {
        /** @var Tag $tag */
        $tag = $entityInstance;

        $taskCount = $tag->getTasks()->count();

        if ($taskCount > 0) {
            $this->addFlash('warning', sprintf(
                'Tag "%s" removed from %d task%s.',
                $tag->getName(),
                $taskCount,
                $taskCount !== 1 ? 's' : '',
            ));
        } else {
            $this->addFlash('success', sprintf('Tag "%s" deleted.', $tag->getName()));
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
