<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\Database\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:make-admin',
    description: 'Grant ROLE_ADMIN to a user',
)]
class MakeAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'User email address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('User with email "%s" not found.', $email));
            return Command::FAILURE;
        }

        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $io->warning(sprintf('User "%s" already has ROLE_ADMIN.', $email));
            return Command::SUCCESS;
        }

        $roles[] = 'ROLE_ADMIN';
        $user->setRoles($roles);
        
        $this->userRepository->save($user, flush: true);

        $io->success(sprintf('ROLE_ADMIN granted to user "%s"', $email));
        $io->info('User roles: ' . implode(', ', $user->getRoles()));

        return Command::SUCCESS;
    }
}

