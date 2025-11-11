<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AuditLog;
use App\Entity\MediaObject;
use App\Entity\RecurrenceRule;
use App\Entity\RefreshToken;
use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\TaskAttachment;
use App\Entity\User;
use App\Enum\TaskStatus;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // === Overview Statistics ===
        $userCount = $this->entityManager->getRepository(User::class)->count([]);
        $taskCount = $this->entityManager->getRepository(Task::class)->count([]);
        $activeRulesCount = $this->entityManager->getRepository(RecurrenceRule::class)->count(['isActive' => true]);

        // Calculate total storage
        $qb = $this->entityManager->createQueryBuilder();
        $totalStorage = $qb->select('SUM(m.fileSize)')
            ->from(MediaObject::class, 'm')
            ->getQuery()
            ->getSingleScalarResult();

        $totalStorageMB = round(($totalStorage ?? 0) / 1024 / 1024, 2);

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

        // === Activity Chart (Last 7 days) ===
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

        // === Recent Activity Feed (Last 20 actions) ===
        $recentActivity = $this->entityManager->getRepository(AuditLog::class)
            ->createQueryBuilder('al')
            ->leftJoin('al.user', 'u')
            ->addSelect('u')
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        // === System Alerts ===
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
            'metrics' => [
                'users' => $userCount,
                'tasks' => $taskCount,
                'activeRules' => $activeRulesCount,
                'storage' => $totalStorageMB,
                'activeUsers24h' => $activeUsersCount,
                'completionRate' => $completionRate,
                'overdueTasks' => $overdueTasksCount,
            ],
            'activityChart' => $activityData,
            'recentActivity' => $recentActivity,
            'alerts' => $alerts,
        ]);
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

        $taskCount = $this->entityManager->getRepository(Task::class)->count([]);
        yield MenuItem::linkToCrud('Tasks', 'fa fa-tasks', Task::class)
            ->setPermission('ROLE_ADMIN')
            ->setBadge($taskCount, 'info');

        yield MenuItem::linkToCrud('Tags', 'fa fa-tags', Tag::class)
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Attachments', 'fa fa-paperclip', TaskAttachment::class)
            ->setPermission('ROLE_ADMIN');

        $activeRulesCount = $this->entityManager->getRepository(RecurrenceRule::class)->count(['isActive' => true]);
        yield MenuItem::linkToCrud('Recurrence Rules', 'fa fa-repeat', RecurrenceRule::class)
            ->setPermission('ROLE_ADMIN')
            ->setBadge($activeRulesCount, 'success');

        yield MenuItem::section('Media Library');
        yield MenuItem::linkToCrud('Media Objects', 'fa fa-file-image-o', MediaObject::class)
            ->setPermission('ROLE_ADMIN');

        yield MenuItem::section('System');
        yield MenuItem::linkToCrud('Refresh Tokens', 'fa fa-key', RefreshToken::class)
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Audit Logs', 'fa fa-history', AuditLog::class)
            ->setPermission('ROLE_SUPER_ADMIN');

        yield MenuItem::section();

        yield MenuItem::linkToUrl('Back to Main Site', 'fa fa-arrow-left', '/')
            ->setLinkTarget('_blank');

        yield MenuItem::linkToRoute('Logout', 'fa fa-sign-out-alt', 'admin_logout');
    }
}
