<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\AnalyticsService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

#[Route('/api/analytics', name: 'api_analytics_')]
#[IsGranted('ROLE_USER')]
#[OA\Tag(name: 'Analytics')]
final class AnalyticsController extends AbstractController
{
    public function __construct(
        private readonly AnalyticsService $analyticsService
    ) {
    }

    #[Route('/overview', name: 'overview', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get analytics overview',
        description: 'Returns key metrics and statistics overview'
    )]
    #[OA\Response(
        response: 200,
        description: 'Analytics overview data'
    )]
    public function getOverview(#[CurrentUser] User $user): JsonResponse
    {
        $data = $this->analyticsService->getOverview($user);
        return $this->json($data);
    }

    #[Route('/completion-timeline', name: 'completion_timeline', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get completion timeline',
        description: 'Returns task completion data over time'
    )]
    #[OA\Parameter(
        name: 'period',
        in: 'query',
        required: false,
        description: 'Number of days (default: 30)',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'dateFrom',
        in: 'query',
        required: false,
        description: 'Start date (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'dateTo',
        in: 'query',
        required: false,
        description: 'End date (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Response(
        response: 200,
        description: 'Timeline data with dates and task counts'
    )]
    public function getCompletionTimeline(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        
        if ($dateFrom && $dateTo) {
            // Custom date range
            $start = new \DateTimeImmutable($dateFrom);
            $end = new \DateTimeImmutable($dateTo);
            $data = $this->analyticsService->getCompletionTimelineByDateRange($user, $start, $end);
        } else {
            // Period-based
            $period = $request->query->getInt('period', 30);
            $data = $this->analyticsService->getCompletionTimeline($user, $period);
        }
        
        return $this->json($data);
    }

    #[Route('/status-distribution', name: 'status_distribution', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get status distribution',
        description: 'Returns task count by status'
    )]
    #[OA\Response(
        response: 200,
        description: 'Status distribution data'
    )]
    public function getStatusDistribution(#[CurrentUser] User $user): JsonResponse
    {
        $data = $this->analyticsService->getStatusDistribution($user);
        return $this->json($data);
    }

    #[Route('/priority-breakdown', name: 'priority_breakdown', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get priority breakdown',
        description: 'Returns task statistics by priority'
    )]
    #[OA\Response(
        response: 200,
        description: 'Priority breakdown data'
    )]
    public function getPriorityBreakdown(#[CurrentUser] User $user): JsonResponse
    {
        $data = $this->analyticsService->getPriorityBreakdown($user);
        return $this->json($data);
    }

    #[Route('/productivity-heatmap', name: 'productivity_heatmap', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get productivity heatmap',
        description: 'Returns GitHub-style activity heatmap data'
    )]
    #[OA\Parameter(
        name: 'year',
        in: 'query',
        required: false,
        description: 'Year (default: current year)',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Heatmap data with date => count mapping'
    )]
    public function getProductivityHeatmap(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $year = $request->query->getInt('year', (int)date('Y'));
        $data = $this->analyticsService->getProductivityHeatmap($user, $year);
        return $this->json($data);
    }

    #[Route('/weekday-productivity', name: 'weekday_productivity', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get weekday productivity',
        description: 'Returns task completion count by day of week'
    )]
    #[OA\Response(
        response: 200,
        description: 'Weekday productivity data'
    )]
    public function getWeekdayProductivity(#[CurrentUser] User $user): JsonResponse
    {
        $data = $this->analyticsService->getWeekdayProductivity($user);
        return $this->json($data);
    }

    #[Route('/top-tags', name: 'top_tags', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get top tags',
        description: 'Returns most used tags with completion statistics'
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        required: false,
        description: 'Number of tags to return (default: 5)',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Top tags with statistics'
    )]
    public function getTopTags(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $limit = $request->query->getInt('limit', 5);
        $data = $this->analyticsService->getTopTags($user, $limit);
        return $this->json($data);
    }

    #[Route('/insights', name: 'insights', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get insights and recommendations',
        description: 'Returns AI-like insights based on user data'
    )]
    #[OA\Response(
        response: 200,
        description: 'Array of insights'
    )]
    public function getInsights(#[CurrentUser] User $user): JsonResponse
    {
        $insights = $this->analyticsService->generateInsights($user);
        return $this->json(['insights' => $insights]);
    }

    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get complete analytics dashboard data',
        description: 'Returns all analytics data in a single optimized request'
    )]
    #[OA\Parameter(
        name: 'period',
        in: 'query',
        required: false,
        description: 'Timeline period in days (default: 30)',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'dateFrom',
        in: 'query',
        required: false,
        description: 'Timeline start date (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'dateTo',
        in: 'query',
        required: false,
        description: 'Timeline end date (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'year',
        in: 'query',
        required: false,
        description: 'Heatmap year (default: current year)',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Complete dashboard analytics data'
    )]
    public function getDashboard(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $period = $request->query->getInt('period', 30);
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        $year = $request->query->getInt('year', (int)date('Y'));

        $data = $this->analyticsService->getDashboardData($user, [
            'period' => $period,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'year' => $year,
        ]);

        return $this->json($data);
    }
}

