<?php

namespace App\Console\Commands;

use App\Models\Position;
use Illuminate\Console\Command;

class RepairInvalidProjectManagerAssignments extends Command
{
    protected $signature = 'irad:repair-project-managers
                            {--dry-run : Report invalid assignments without changing data}';

    protected $description = 'Remove position Project Manager assignments from users who do not have the project_manager role.';

    public function handle(): int
    {
        $invalidPositions = Position::query()
            ->whereNotNull('project_manager_user_id')
            ->whereDoesntHave('projectManager.roles', function ($query) {
                $query->where('roles.name', 'project_manager');
            })
            ->with('projectManager:id,name,email')
            ->orderBy('position_code')
            ->get();

        if ($invalidPositions->isEmpty()) {
            $this->info('No invalid Project Manager assignments were found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Position', 'Assigned User', 'Email'],
            $invalidPositions->map(fn (Position $position) => [
                $position->position_code,
                $position->projectManager?->name ?? 'Unknown',
                $position->projectManager?->email ?? 'Unknown',
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->warn('Dry run only. No assignments were changed.');

            return self::SUCCESS;
        }

        Position::query()
            ->whereKey($invalidPositions->pluck('id'))
            ->update(['project_manager_user_id' => null]);

        $this->info(
            $invalidPositions->count().' invalid Project Manager assignment(s) were cleared.'
        );

        return self::SUCCESS;
    }
}
