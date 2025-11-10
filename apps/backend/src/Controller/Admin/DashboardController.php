<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\TaskAttachment;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Redirect to Users CRUD by default
        $url = $this->adminUrlGenerator
            ->setController(UserCrudController::class)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Auth App - Admin Panel')
            ->setFaviconPath('favicon.svg')
            
            // Enable dark mode
            ->setDefaultColorScheme('auto')
            
            // Sidebar settings
            ->renderSidebarMinimized(false)
            
            // Content settings
            ->renderContentMaximized(false);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('User Management');
        yield MenuItem::linkToCrud('Users', 'fa fa-users', User::class)
            ->setPermission('ROLE_ADMIN');

        yield MenuItem::section('Task Management');
        yield MenuItem::linkToCrud('Tasks', 'fa fa-tasks', Task::class)
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Tags', 'fa fa-tags', Tag::class)
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Attachments', 'fa fa-paperclip', TaskAttachment::class)
            ->setPermission('ROLE_ADMIN');

        yield MenuItem::section();

        yield MenuItem::linkToUrl('Back to Main Site', 'fa fa-arrow-left', '/')
            ->setLinkTarget('_blank');

        yield MenuItem::linkToRoute('Logout', 'fa fa-sign-out-alt', 'admin_logout');
    }
}
