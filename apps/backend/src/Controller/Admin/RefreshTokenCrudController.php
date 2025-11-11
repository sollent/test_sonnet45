<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RefreshTokenCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return RefreshToken::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Refresh Token')
            ->setEntityLabelInPlural('Refresh Tokens')
            ->setPageTitle('index', 'JWT Refresh Tokens')
            ->setPageTitle('detail', fn (RefreshToken $token) => sprintf('Token for: %s', $token->getUsername() ?? 'Unknown'))

            // Pagination
            ->setPaginatorPageSize(30)
            ->setPaginatorRangeSize(5)

            // Search - by username
            ->setSearchFields(['username'])

            // Default sort - newest first (by ID, since no createdAt field)
            ->setDefaultSort(['id' => 'DESC'])

            // Date format
            ->setDateTimeFormat('dd.MM.yyyy HH:mm:ss')

            // Other settings
            ->showEntityActionsInlined()
            ->setEntityPermission('ROLE_ADMIN');
    }

    public function configureFields(string $pageName): iterable
    {
        // ID field
        yield IdField::new('id', 'ID')
            ->onlyOnIndex();

        // Username field
        yield TextField::new('username', 'Username')
            ->setColumns('col-md-4');

        // Refresh Token field - masked for security
        yield TextField::new('refreshToken', 'Token')
            ->formatValue(function ($value) {
                if (empty($value)) {
                    return '<span class="text-muted">N/A</span>';
                }
                // Show only last 8 characters for security
                $masked = str_repeat('*', max(0, strlen($value) - 8)) . substr($value, -8);
                return sprintf('<code class="text-muted">%s</code>', htmlspecialchars($masked));
            })
            ->setColumns('col-md-4')
            ->onlyOnIndex();

        // Full token on detail page only
        if (Crud::PAGE_DETAIL === $pageName) {
            yield TextField::new('refreshToken', 'Full Token')
                ->formatValue(function ($value) {
                    return sprintf('<code class="bg-light p-2 d-block" style="word-break: break-all;">%s</code>', htmlspecialchars($value));
                })
                ->onlyOnDetail();
        }

        // Valid Until field
        yield DateTimeField::new('valid', 'Valid Until')
            ->setColumns('col-md-3');

        // Is Valid field - computed (shows if token is expired or not)
        yield Field::new('isValid', 'Status')
            ->formatValue(function ($value, RefreshToken $token) {
                $isValid = $token->isValid();
                $badgeClass = $isValid ? 'success' : 'danger';
                $text = $isValid ? 'Valid' : 'Expired';

                return sprintf('<span class="badge badge-%s">%s</span>', $badgeClass, $text);
            })
            ->setColumns('col-md-2');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            // Username filter
            ->add(TextFilter::new('username', 'Username'))

            // Valid Until filter
            ->add(DateTimeFilter::new('valid', 'Valid Until'))

            // Custom filter for expired tokens
            ->add(BooleanFilter::new('isExpired', 'Show Only Expired')
                ->setFormTypeOption('choices', [
                    'Yes' => true,
                    'No' => false,
                ])
            );
    }

    public function configureActions(Actions $actions): Actions
    {
        // Custom action to cleanup expired tokens
        $cleanupExpiredAction = Action::new('cleanupExpired', 'Cleanup Expired Tokens')
            ->linkToCrudAction('cleanupExpired')
            ->createAsGlobalAction()
            ->setIcon('fa fa-broom')
            ->addCssClass('btn btn-warning');

        return $actions
            // Disable NEW and EDIT actions (tokens are created automatically by the system)
            ->disable(Action::NEW, Action::EDIT)

            // Add detail action
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            // Add custom cleanup action
            ->add(Crud::PAGE_INDEX, $cleanupExpiredAction)

            // Customize action labels
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action
                ->setIcon('fa fa-trash')
                ->setLabel('Revoke')
                ->displayIf(fn (RefreshToken $token) => true) // Allow deleting any token
            )
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action
                ->setIcon('fa fa-eye')
                ->setLabel(false)
            );
    }

    /**
     * Custom action to cleanup all expired tokens
     */
    public function cleanupExpired(AdminContext $context): Response
    {
        try {
            $qb = $this->entityManager->createQueryBuilder();
            $count = $qb->delete(RefreshToken::class, 'rt')
                ->where('rt.valid < :now')
                ->setParameter('now', new \DateTime())
                ->getQuery()
                ->execute();

            $this->addFlash('success', sprintf('Successfully deleted %d expired token(s)!', $count));
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('Error cleaning up tokens: %s', $e->getMessage()));
        }

        // Redirect back to index page
        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return new RedirectResponse($url);
    }
}
