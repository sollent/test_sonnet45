<?php

declare(strict_types=1);

namespace App\Dto\Response;

use OpenApi\Attributes as OA;

/**
 * DTO для ответа с историей голосовых команд
 */
#[OA\Schema(
    title: 'Voice Command History Response',
    description: 'Response with list of voice commands history',
    type: 'object',
)]
class VoiceCommandHistoryResponse
{
    /**
     * @var VoiceCommandResponse[]
     */
    #[OA\Property(
        description: 'List of voice commands',
        type: 'array',
        items: new OA\Items(ref: '#/components/schemas/VoiceCommandResponse'),
    )]
    public array $commands = [];

    #[OA\Property(description: 'Total number of commands in result', example: 15)]
    public int $total;

    #[OA\Property(description: 'Limit used in query', example: 20)]
    public int $limit;

    #[OA\Property(description: 'Offset used in query', example: 0)]
    public int $offset;
}
