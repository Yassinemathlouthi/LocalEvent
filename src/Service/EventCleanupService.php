<?php

namespace App\Service;

use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class EventCleanupService
{
    private $eventRepository;
    private $entityManager;
    private $logger;

    public function __construct(
        EventRepository $eventRepository, 
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->eventRepository = $eventRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Remove expired events
     * 
     * @return int Number of events removed
     */
    public function removeExpiredEvents(): int
    {
        $today = new \DateTime('today');
        $expiredEvents = $this->eventRepository->findExpiredEvents($today);
        
        $count = count($expiredEvents);
        
        if ($count === 0) {
            $this->logger->info('No expired events found for removal.');
            return 0;
        }
        
        foreach ($expiredEvents as $event) {
            $this->entityManager->remove($event);
            $this->logger->info(sprintf('Removed expired event: %s (ID: %d)', $event->getTitle(), $event->getId()));
        }
        
        $this->entityManager->flush();
        
        $this->logger->info(sprintf('Removed %d expired events.', $count));
        
        return $count;
    }
} 