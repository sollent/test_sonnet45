<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AuditLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class AuditLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AuditLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Audit Log')
            ->setEntityLabelInPlural('Audit Logs')
            ->setPageTitle('index', 'Audit Log Trail')
            ->setPageTitle('detail', fn (AuditLog $log) => sprintf('%s %s #%s', $log->getAction(), $log->getEntityType(), $log->getEntityId() ?? 'N/A'))

            // Pagination - show more entries for audit logs
            ->setPaginatorPageSize(50)
            ->setPaginatorRangeSize(5)

            // Search - by action, entity type, and entity ID
            ->setSearchFields(['action', 'entityType', 'entityId'])

            // Default sort - newest first
            ->setDefaultSort(['createdAt' => 'DESC'])

            // Date format
            ->setDateTimeFormat('dd.MM.yyyy HH:mm:ss')

            // Other settings
            ->showEntityActionsInlined()
            ->setEntityPermission('ROLE_SUPER_ADMIN'); // Only super admins can view audit logs
    }

    public function configureFields(string $pageName): iterable
    {
        // ID field - only on index
        yield IdField::new('id', 'ID')
            ->onlyOnIndex();

        // Action field with badge formatting
        yield Field::new('action', 'Action')
            ->formatValue(function ($value) {
                $badgeClass = match ($value) {
                    'CREATE' => 'success',
                    'UPDATE' => 'info',
                    'DELETE' => 'danger',
                    'LOGIN' => 'primary',
                    'LOGOUT' => 'secondary',
                    default => 'dark',
                };

                return sprintf('<span class="badge badge-%s">%s</span>', $badgeClass, $value);
            });

        // Entity Type field
        yield TextField::new('entityType', 'Entity')
            ->setColumns('col-md-3');

        // Entity ID field with link (if applicable)
        yield IntegerField::new('entityId', 'Entity ID')
            ->setColumns('col-md-2')
            ->onlyOnIndex();

        // User field - association
        yield AssociationField::new('user', 'Performed By')
            ->setColumns('col-md-3')
            ->formatValue(function ($value, AuditLog $log) {
                $user = $log->getUser();
                return $user ? $user->getEmail() : '<span class="text-muted">System</span>';
            });

        // Created At field - timestamp
        yield DateTimeField::new('createdAt', 'Timestamp')
            ->setColumns('col-md-3');

        // Detail page only fields
        if (Crud::PAGE_DETAIL === $pageName) {
            // Old Data - JSON display
            yield Field::new('oldData', 'Old Data')
                ->formatValue(function ($value) {
                    if (empty($value)) {
                        return '<span class="text-muted">N/A</span>';
                    }
                    return sprintf('<pre class="bg-light p-2">%s</pre>', json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                })
                ->onlyOnDetail();

            // New Data - JSON display
            yield Field::new('newData', 'New Data')
                ->formatValue(function ($value) {
                    if (empty($value)) {
                        return '<span class="text-muted">N/A</span>';
                    }
                    return sprintf('<pre class="bg-light p-2">%s</pre>', json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                })
                ->onlyOnDetail();

            // Metadata - IP, User Agent, Route
            yield Field::new('metadata', 'Request Metadata')
                ->formatValue(function ($value) {
                    if (empty($value)) {
                        return '<span class="text-muted">N/A</span>';
                    }

                    $html = '<div class="mb-2">';

                    if (isset($value['ip'])) {
                        $html .= sprintf('<div><strong>IP:</strong> %s</div>', htmlspecialchars($value['ip']));
                    }

                    if (isset($value['user_agent'])) {
                        $html .= sprintf('<div><strong>User Agent:</strong> <small>%s</small></div>', htmlspecialchars($value['user_agent']));
                    }

                    if (isset($value['route'])) {
                        $html .= sprintf('<div><strong>Route:</strong> <code>%s</code></div>', htmlspecialchars($value['route']));
                    }

                    if (isset($value['method'])) {
                        $html .= sprintf('<div><strong>HTTP Method:</strong> <span class="badge badge-secondary">%s</span></div>', htmlspecialchars($value['method']));
                    }

                    $html .= '</div>';
                    return $html;
                })
                ->onlyOnDetail();
        }
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            // Action filter (CREATE, UPDATE, DELETE, etc.)
            ->add(ChoiceFilter::new('action', 'Action')
                ->setChoices([
                    'Create' => 'CREATE',
                    'Update' => 'UPDATE',
                    'Delete' => 'DELETE',
                    'Login' => 'LOGIN',
                    'Logout' => 'LOGOUT',
                ])
            )

            // Entity Type filter
            ->add(TextFilter::new('entityType', 'Entity Type'))

            // Entity ID filter
            ->add(TextFilter::new('entityId', 'Entity ID'))

            // User filter
            ->add(EntityFilter::new('user', 'User'))

            // Date range filters
            ->add(DateTimeFilter::new('createdAt', 'Timestamp'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Remove NEW, EDIT, DELETE actions (read-only)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)

            // Add DETAIL action to view full log entry
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            // Customize detail action
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action
                ->setIcon('fa fa-eye')
                ->setLabel('View Details')
            );
    }
}
