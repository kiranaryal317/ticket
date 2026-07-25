<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Ticket: ' . $this->ticket->subject)
            ->greeting('New ticket opened')
            ->line($this->ticket->subject)
            ->line('Priority: ' . $this->ticket->priority)
            ->action('View Ticket', url('/tickets/' . $this->ticket->id));
    }
}