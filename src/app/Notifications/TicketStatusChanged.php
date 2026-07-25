<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChanged extends Notification implements ShouldQueue
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
            ->subject('Ticket Status Updated: ' . $this->ticket->subject)
            ->greeting('Your ticket status changed')
            ->line($this->ticket->subject)
            ->line('New status: ' . $this->ticket->status)
            ->action('View Ticket', url('/tickets/' . $this->ticket->id));
    }
}