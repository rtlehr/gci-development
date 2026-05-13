<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Alert $alert)
    {
    }

    public function build()
    {
        return $this
            ->subject($this->alert->title)
            ->view('emails.alert-notification');
    }
}