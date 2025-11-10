<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
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
            
            // Pagination
            ->setPaginatorPageSize(25)
            ->setPaginatorRangeSize(4)
            
            // Search
            ->setSearchFields(['email', 'googleUserName', 'googleId'])
            
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
        // ID field - only on index and detail
        yield IdField::new('id', 'ID')
            ->onlyOnIndex();

        // Email field - required, searchable
        yield EmailField::new('email', 'Email')
            ->setRequired(true)
            ->setHelp('User email address (unique)')
            ->setColumns('col-md-6');

        // Password field - only on forms, not on list/detail
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

        // Google User Name - optional
        yield TextField::new('googleUserName', 'Google Name')
            ->setHelp('Name from Google account (if authenticated via Google)')
            ->hideOnForm()
            ->setColumns('col-md-4');

        // Google ID - readonly on edit
        yield TextField::new('googleId', 'Google ID')
            ->setHelp('Unique Google user ID (if authenticated via Google)')
            ->hideOnForm()
            ->setColumns('col-md-4');

        // Roles field
        yield ArrayField::new('roles', 'Roles')
            ->setHelp('User roles')
            ->hideOnIndex();

        // Has Google Auth - computed field
        yield BooleanField::new('hasGoogleAuth', 'Google Authentication')
            ->renderAsSwitch(false)
            ->setHelp('Authenticated via Google')
            ->hideOnForm();

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
            // Text filters
            ->add(TextFilter::new('email', 'Email'))
            ->add(TextFilter::new('googleUserName', 'Google Name'))
            ->add(TextFilter::new('googleId', 'Google ID'))
            
            // Boolean filter for Google users
            ->add(
                BooleanFilter::new('hasGoogleAuth', 'Has Google Auth')
                    ->setFormTypeOption('expanded', false)
            )
            
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

