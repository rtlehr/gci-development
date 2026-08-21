<?php

namespace App\Console\Commands;

use App\Services\Encryption\EncryptionManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class EncryptExistingData extends Command
{
    protected $signature = 'irad:encrypt-existing-data
        {--pretend : Report legacy plaintext values without changing the database}
        {--chunk=200 : Number of rows to process per chunk}';

    protected $description = 'Encrypt legacy plaintext values for fields protected by IRAD data encryption.';

    /**
     * Fields intentionally included in Step 2. Searchable/sortable sensitive
     * fields are deferred until a lookup/search strategy is introduced.
     *
     * @var array<string, array<int, string>>
     */
    private const FIELDS = [
        'people' => ['notes'],
        'addresses' => ['line_1', 'line_2', 'city', 'state', 'postal_code', 'country', 'notes'],
        'person_phone_numbers' => ['extension', 'notes'],
        'person_notes' => ['note'],
        'candidate_step_events' => ['notes', 'comments'],
        'tickets' => ['source_url', 'resolution_notes'],
        'ticket_activities' => ['old_value', 'new_value', 'comment'],
    ];

    public function handle(EncryptionManager $encryption): int
    {
        $pretend = (bool) $this->option('pretend');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $examined = 0;
        $encrypted = 0;
        $alreadyEncrypted = 0;

        $this->info($pretend
            ? 'Scanning for legacy plaintext values (pretend mode; no changes will be written)...'
            : 'Encrypting legacy plaintext values...');

        try {
            foreach (self::FIELDS as $table => $columns) {
                $tableEncrypted = 0;

                DB::table($table)
                    ->select(array_merge(['id'], $columns))
                    ->orderBy('id')
                    ->chunkById($chunkSize, function ($rows) use (
                        $table,
                        $columns,
                        $encryption,
                        $pretend,
                        &$examined,
                        &$encrypted,
                        &$alreadyEncrypted,
                        &$tableEncrypted,
                    ): void {
                        foreach ($rows as $row) {
                            $updates = [];

                            foreach ($columns as $column) {
                                $value = $row->{$column};

                                if ($value === null) {
                                    continue;
                                }

                                $examined++;

                                if ($encryption->isEncrypted($value)) {
                                    $alreadyEncrypted++;
                                    continue;
                                }

                                $encrypted++;
                                $tableEncrypted++;

                                if (! $pretend) {
                                    $updates[$column] = $encryption->encrypt($value);
                                }
                            }

                            if (! $pretend && $updates !== []) {
                                DB::table($table)
                                    ->where('id', $row->id)
                                    ->update($updates);
                            }
                        }
                    });

                $this->line(sprintf(
                    '  %s: %d plaintext value%s %s',
                    $table,
                    $tableEncrypted,
                    $tableEncrypted === 1 ? '' : 's',
                    $pretend ? 'would be encrypted' : 'encrypted',
                ));
            }
        } catch (Throwable $exception) {
            $this->error('Encryption migration stopped: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info(sprintf(
            'Complete. Examined %d non-null values; %d %s; %d already encrypted.',
            $examined,
            $encrypted,
            $pretend ? 'would be encrypted' : 'encrypted',
            $alreadyEncrypted,
        ));

        return self::SUCCESS;
    }
}
