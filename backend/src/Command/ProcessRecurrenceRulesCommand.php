<?php

namespace App\Command;

use App\Service\RecurrenceService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process-recurrence-rules',
    description: 'Process recurrence rules and generate scheduled tasks',
)]
class ProcessRecurrenceRulesCommand extends Command
{
    public function __construct(
        private readonly RecurrenceService $recurrenceService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run in dry-run mode (no tasks will be created)'
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Limit the number of rules to process',
                100
            )
            ->setHelp('This command processes active recurrence rules and generates tasks according to their schedules.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $limit = (int) $input->getOption('limit');
        
        $io->title('Processing Recurrence Rules');
        
        if ($isDryRun) {
            $io->warning('Running in DRY-RUN mode - no tasks will be created');
        }
        
        try {
            $startTime = microtime(true);
            $now = new \DateTime();
            
            $io->section('Starting process at ' . $now->format('Y-m-d H:i:s'));
            
            // Process rules
            if ($isDryRun) {
                $io->note('Dry-run mode: Simulating task generation...');
                $processedCount = $this->simulateProcessing($io, $limit);
            } else {
                $processedCount = $this->recurrenceService->processRecurrenceRules($now);
            }
            
            $executionTime = round(microtime(true) - $startTime, 2);
            
            // Summary
            $io->success([
                sprintf('Processed %d recurrence rules', $processedCount),
                sprintf('Execution time: %s seconds', $executionTime),
            ]);
            
            // Log the execution
            $this->logger->info('Recurrence rules processed', [
                'processed_count' => $processedCount,
                'execution_time' => $executionTime,
                'dry_run' => $isDryRun,
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error([
                'An error occurred while processing recurrence rules',
                $e->getMessage(),
            ]);
            
            $this->logger->error('Failed to process recurrence rules', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }
    
    private function simulateProcessing(SymfonyStyle $io, int $limit): int
    {
        // Simulate processing for dry-run mode
        $io->progressStart($limit);
        
        for ($i = 0; $i < $limit; $i++) {
            usleep(10000); // Simulate processing time
            $io->progressAdvance();
        }
        
        $io->progressFinish();
        
        return $limit;
    }
}
