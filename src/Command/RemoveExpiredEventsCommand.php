<?php

namespace App\Command;

use App\Service\EventCleanupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:remove-expired-events',
    description: 'Remove events that have already passed',
)]
class RemoveExpiredEventsCommand extends Command
{
    private $eventCleanupService;

    public function __construct(EventCleanupService $eventCleanupService)
    {
        parent::__construct();
        $this->eventCleanupService = $eventCleanupService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $count = $this->eventCleanupService->removeExpiredEvents();
        
        if ($count === 0) {
            $io->success('No expired events found.');
        } else {
            $io->success(sprintf('%d expired events have been removed.', $count));
        }
        
        return Command::SUCCESS;
    }
} 