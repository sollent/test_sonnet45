<?php

declare(strict_types=1);

namespace App\Dto\Response;

use OpenApi\Attributes as OA;

/**
 * DTO для ответа со статистикой использования голосовых команд
 */
#[OA\Schema(
    title: 'Voice Command Statistics Response',
    description: 'Response with voice commands usage statistics',
    type: 'object',
)]
class VoiceCommandStatisticsResponse
{
    #[OA\Property(description: 'Total number of commands', example: 150)]
    public int $totalCommands;

    #[OA\Property(
        description: 'Commands count by status',
        example: [
            'pending'    => 2,
            'processing' => 1,
            'executing'  => 0,
            'completed'  => 120,
            'failed'     => 27,
        ],
    )]
    public array $commandsByStatus;

    #[OA\Property(description: 'Average processing duration in milliseconds', example: 850)]
    public float $averageDurationMs;

    #[OA\Property(description: 'Success rate percentage', example: 80.5)]
    public float $successRate;

    #[OA\Property(
        description: 'Most used actions',
        example: [
            'create_task'   => 65,
            'complete_task' => 40,
            'filter_tasks'  => 15,
        ],
    )]
    public ?array $mostUsedActions = null;

    #[OA\Property(
        description: 'Commands by day (last 7 days)',
        example: [
            '2024-01-09' => 5,
            '2024-01-10' => 8,
            '2024-01-11' => 12,
            '2024-01-12' => 7,
            '2024-01-13' => 10,
            '2024-01-14' => 15,
            '2024-01-15' => 9,
        ],
    )]
    public ?array $commandsByDay = null;
}
