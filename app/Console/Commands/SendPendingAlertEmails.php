<?php

namespace App\Console\Commands;

use App\Mail\AlertNotificationMail;
use App\Models\Alert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPendingAlertEmails extends Command
{
    protected $signature = 'alerts:send-emails';

    protected $description = 'Send pending alert notification emails';

    public function handle(): int
    {
        $alerts = Alert::query()
            ->with('user')
            ->where('should_email', true)
            ->whereNull('emailed_at')
            ->whereNull('email_queued_at')
            ->limit(50)
            ->get();

        foreach ($alerts as $alert) {
            try {
                if (! $alert->user || blank($alert->user->email)) {
                    continue;
                }

                $alert->update([
                    'email_queued_at' => now(),
                    'email_error' => null,
                ]);

                Mail::to($alert->user->email)
                    ->send(new AlertNotificationMail($alert));

                $alert->update([
                    'emailed_at' => now(),
                    'email_error' => null,
                ]);
            } catch (\Throwable $e) {
                $alert->update([
                    'email_queued_at' => null,
                    'email_error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Processed {$alerts->count()} alert emails.");

        return self::SUCCESS;
    }
}