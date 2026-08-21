<?php

namespace App\Console\Commands;

use App\Models\UserEventLog;
use Illuminate\Console\Command;

class PruneUserEventLog extends Command
{
    protected $signature = 'user-event-log:prune {--days= : Override the configured retention period} {--pretend : Show what would be deleted without deleting it}';

    protected $description = 'Delete User Event Log entries older than the configured retention period';

    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('user-event-log.retention_days', 0);

        if ($days <= 0) {
            $this->info('User Event Log retention is disabled; no events were deleted.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $query = UserEventLog::query()->where('occurred_at', '<', $cutoff);
        $count = (clone $query)->count();

        if ((bool) $this->option('pretend')) {
            $this->info("{$count} event(s) would be deleted before {$cutoff->toDateTimeString()}.");

            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Deleted {$deleted} User Event Log event(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
