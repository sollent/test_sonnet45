<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\Response\Task\TaskResponseDto;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:test-enum-serialization',
    description: 'Test how Symfony Serializer handles enums in TaskResponseDto'
)]
class TestEnumSerializationCommand extends Command
{
    public function __construct(
        private readonly SerializerInterface $serializer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('=== Testing Symfony Serializer Enum Handling ===');
        $output->writeln('');

        // Create simple DTO with enums
        $dto = new TaskResponseDto();
        $dto->id = 999;
        $dto->title = "Test Task for Enum Serialization";
        $dto->description = "Testing how enums are serialized";
        $dto->status = TaskStatus::IN_PROGRESS;
        $dto->priority = TaskPriority::HIGH;
        $dto->tags = [];
        $dto->subtasks = [];
        $dto->attachments = [];
        $dto->isCompleted = false;
        $dto->isArchived = false;

        $output->writeln('1. Original DTO created:');
        $output->writeln('   ID: ' . $dto->id);
        $output->writeln('   Status: ' . $dto->status->value . ' (enum ' . $dto->status->name . ')');
        $output->writeln('   Priority: ' . $dto->priority->value . ' (enum ' . $dto->priority->name . ')');
        $output->writeln('');

        // Serialize with Symfony Serializer
        $output->writeln('2. Serializing with Symfony Serializer...');
        $json = $this->serializer->serialize($dto, 'json', ['groups' => ['task:read']]);

        $output->writeln('   JSON output:');
        $formatted = json_encode(json_decode($json), JSON_PRETTY_PRINT);
        foreach (explode("\n", $formatted) as $line) {
            $output->writeln('   ' . $line);
        }
        $output->writeln('');

        // Decode to array
        $output->writeln('3. Decoding JSON to array...');
        $array = json_decode($json, true);

        $output->writeln('   status field:');
        $output->writeln('      Type: ' . gettype($array['status']));
        $output->writeln('      Value: ' . var_export($array['status'], true));

        $output->writeln('   priority field:');
        $output->writeln('      Type: ' . gettype($array['priority']));
        $output->writeln('      Value: ' . var_export($array['priority'], true));
        $output->writeln('');

        // Test deserialization
        $output->writeln('4. Testing TaskResponseDto::fromArray()...');
        try {
            $deserialized = TaskResponseDto::fromArray($array);
            $output->writeln('   <fg=green>✅ SUCCESS!</>');
            $output->writeln('   Deserialized status: ' . $deserialized->status->value);
            $output->writeln('   Deserialized priority: ' . $deserialized->priority->value);

            if ($deserialized->status === $dto->status && $deserialized->priority === $dto->priority) {
                $output->writeln('   <fg=green>✅ Enums match original!</>');
            } else {
                $output->writeln('   <fg=yellow>⚠️  Warning: Enums do not match original</>');
            }
        } catch (\Throwable $e) {
            $output->writeln('   <fg=red>❌ FAILED!</>');
            $output->writeln('   Error: ' . $e->getMessage());
            $output->writeln('   File: ' . $e->getFile() . ':' . $e->getLine());

            $output->writeln('');
            $output->writeln('<fg=red>Stack trace:</>');
            $output->writeln($e->getTraceAsString());

            return Command::FAILURE;
        }

        $output->writeln('');
        $output->writeln('=== Test Complete ===');

        return Command::SUCCESS;
    }
}
