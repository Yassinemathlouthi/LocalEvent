<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Attendance;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class NotificationService
{
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;
    private string $senderEmail;

    public function __construct(
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        string $senderEmail = 'noreply@localevent.com'
    ) {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->senderEmail = $senderEmail;
    }

    /**
     * Send event confirmation when a new event is created
     */
    public function sendEventCreationConfirmation(Event $event): void
    {
        $email = (new TemplatedEmail())
            ->from($this->senderEmail)
            ->to($event->getOrganizer()->getEmail())
            ->subject('Your event has been submitted - LocalEvent')
            ->htmlTemplate('emails/event_created.html.twig')
            ->context([
                'event' => $event,
                'user' => $event->getOrganizer(),
                'eventUrl' => $this->urlGenerator->generate('event_show', ['id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send notification when an event is approved
     */
    public function sendEventApprovalNotification(Event $event): void
    {
        $email = (new TemplatedEmail())
            ->from($this->senderEmail)
            ->to($event->getOrganizer()->getEmail())
            ->subject('Your event has been approved - LocalEvent')
            ->htmlTemplate('emails/event_approved.html.twig')
            ->context([
                'event' => $event,
                'user' => $event->getOrganizer(),
                'eventUrl' => $this->urlGenerator->generate('event_show', ['id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send notification when user joins an event
     */
    public function sendEventJoinNotification(Attendance $attendance): void
    {
        // Notify the event organizer
        $organizerEmail = (new TemplatedEmail())
            ->from($this->senderEmail)
            ->to($attendance->getEvent()->getOrganizer()->getEmail())
            ->subject('Someone has joined your event - LocalEvent')
            ->htmlTemplate('emails/event_joined_organizer.html.twig')
            ->context([
                'event' => $attendance->getEvent(),
                'user' => $attendance->getUser(),
                'eventUrl' => $this->urlGenerator->generate('event_show', ['id' => $attendance->getEvent()->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);

        $this->mailer->send($organizerEmail);

        // Confirmation to the attendee
        $attendeeEmail = (new TemplatedEmail())
            ->from($this->senderEmail)
            ->to($attendance->getUser()->getEmail())
            ->subject('Event registration confirmed - LocalEvent')
            ->htmlTemplate('emails/event_joined_attendee.html.twig')
            ->context([
                'event' => $attendance->getEvent(),
                'user' => $attendance->getUser(),
                'eventUrl' => $this->urlGenerator->generate('event_show', ['id' => $attendance->getEvent()->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);

        $this->mailer->send($attendeeEmail);
    }

    /**
     * Send event reminder (can be scheduled)
     */
    public function sendEventReminder(Event $event): void
    {
        $attendances = $event->getAttendances();
        
        foreach ($attendances as $attendance) {
            if ($attendance->getStatus() === Attendance::STATUS_JOINED) {
                $user = $attendance->getUser();
                
                $email = (new TemplatedEmail())
                    ->from($this->senderEmail)
                    ->to($user->getEmail())
                    ->subject('Reminder: Upcoming event - LocalEvent')
                    ->htmlTemplate('emails/event_reminder.html.twig')
                    ->context([
                        'event' => $event,
                        'user' => $user,
                        'eventUrl' => $this->urlGenerator->generate('event_show', ['id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                    ]);

                $this->mailer->send($email);
            }
        }
    }

    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from($this->senderEmail)
            ->to($user->getEmail())
            ->subject('Welcome to LocalEvent!')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'user' => $user,
                'dashboardUrl' => $this->urlGenerator->generate('app_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);

        $this->mailer->send($email);
    }
}
