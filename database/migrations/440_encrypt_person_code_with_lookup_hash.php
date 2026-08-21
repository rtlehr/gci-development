<?php

use App\Services\Encryption\EncryptionManager;
use App\Services\Encryption\LookupHashService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->char('person_code_lookup', 64)->nullable()->after('person_code');
            $table->dropUnique('people_person_code_unique');
            $table->text('person_code')->nullable()->change();
        });

        $encryption = app(EncryptionManager::class);
        $lookup = app(LookupHashService::class);

        DB::table('people')
            ->select(['id', 'person_code'])
            ->whereNotNull('person_code')
            ->orderBy('id')
            ->chunkById(100, function ($people) use ($encryption, $lookup): void {
                foreach ($people as $person) {
                    $raw = (string) $person->person_code;
                    $plain = $encryption->isEncrypted($raw)
                        ? (string) $encryption->decrypt($raw)
                        : $raw;

                    DB::table('people')
                        ->where('id', $person->id)
                        ->update([
                            'person_code' => $encryption->isEncrypted($raw) ? $raw : $encryption->encrypt($plain),
                            'person_code_lookup' => $lookup->hash($plain),
                        ]);
                }
            });

        Schema::table('people', function (Blueprint $table): void {
            $table->unique('person_code_lookup');
        });
    }

    public function down(): void
    {
        /*
         * Do not automatically decrypt person_code values or remove the lookup
         * index during rollback. Doing so could expose protected identifiers or
         * make an interrupted rollback destructive. Restore from backup if this
         * migration ever needs to be reversed in a populated environment.
         */
    }
};
