<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssigned extends Notification implements ShouldQueue
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
            ->subject('Ticket Assigned to You: ' . $this->ticket->subject)
            ->greeting('A ticket has been assigned to you')
            ->line($this->ticket->subject)
            ->line('Priority: ' . $this->ticket->priority)
            ->action('View Ticket', url('/tickets/' . $this->ticket->id));
    }
}