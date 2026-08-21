<?php

namespace App\Console\Commands;

use App\Services\Encryption\EncryptionManager;
use App\Services\Encryption\LookupHashService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildEncryptionLookups extends Command
{
    protected $signature = 'irad:rebuild-encryption-lookups {--pretend : Report what would be rebuilt without changing data}';

    protected $description = 'Rebuild deterministic lookup hashes for encrypted searchable IRAD fields.';

    public function handle(EncryptionManager $encryption, LookupHashService $lookup): int
    {
        $rows = DB::table('people')
            ->select(['id', 'person_code'])
            ->whereNotNull('person_code')
            ->orderBy('id')
            ->get();

        if ($this->option('pretend')) {
            $this->info("Would rebuild {$rows->count()} person_code lookup hash(es).");

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows, $encryption, $lookup): void {
            DB::table('people')->whereNotNull('person_code')->update(['person_code_lookup' => null]);

            foreach ($rows as $row) {
                $raw = (string) $row->person_code;
                $plain = $encryption->isEncrypted($raw)
                    ? (string) $encryption->decrypt($raw)
                    : $raw;

                DB::table('people')
                    ->where('id', $row->id)
                    ->update(['person_code_lookup' => $lookup->hash($plain)]);
            }
        });

        $this->info("Rebuilt {$rows->count()} person_code lookup hash(es).");

        return self::SUCCESS;
    }
}
