<?php

namespace App\Mail;

use App\Models\Alert;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public ?Ticket $ticket = null;

    public function __construct(public Alert $alert)
    {
        if (
            $alert->source_type === 'ticket' &&
            $alert->source_id
        ) {
            $this->ticket = Ticket::find($alert->source_id);
        }
    }

    public function build()
    {
        $subject = $this->alert->title;

        if ($this->ticket) {
            $subject = "A new ticket has been submitted, {$this->ticket->ticket_number}";
        }

        return $this
            ->subject($subject)
            ->view('emails.alert-notification', [
                'alert' => $this->alert,
                'ticket' => $this->ticket,
            ]);
    }
}