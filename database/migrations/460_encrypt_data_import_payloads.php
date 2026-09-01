<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_imports', function (Blueprint $table): void {
            $table->longText('workbook_metadata_encrypted')->nullable();
            $table->longText('mapping_snapshot_encrypted')->nullable();
        });

        Schema::table('data_import_rows', function (Blueprint $table): void {
            $table->longText('source_identifier_encrypted')->nullable();
            $table->longText('issues_encrypted')->nullable();
            $table->longText('result_encrypted')->nullable();
        });

        DB::table('data_imports')
            ->select(['id', 'workbook_metadata', 'mapping_snapshot'])
            ->orderBy('id')
            ->chunkById(100, function ($imports): void {
                foreach ($imports as $import) {
                    DB::table('data_imports')->where('id', $import->id)->update([
                        'workbook_metadata_encrypted' => $this->encryptJson($import->workbook_metadata),
                        'mapping_snapshot_encrypted' => $this->encryptJson($import->mapping_snapshot),
                        'workbook_metadata' => null,
                        'mapping_snapshot' => null,
                    ]);
                }
            });

        DB::table('data_import_rows')
            ->select(['id', 'source_identifier', 'issues', 'result'])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('data_import_rows')->where('id', $row->id)->update([
                        'source_identifier_encrypted' => filled($row->source_identifier) ? Crypt::encryptString((string) $row->source_identifier) : null,
                        'issues_encrypted' => $this->encryptJson($row->issues),
                        'result_encrypted' => $this->encryptJson($row->result),
                        'source_identifier' => null,
                        'issues' => null,
                        'result' => null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('data_imports')
            ->select(['id', 'workbook_metadata_encrypted', 'mapping_snapshot_encrypted'])
            ->orderBy('id')
            ->chunkById(100, function ($imports): void {
                foreach ($imports as $import) {
                    DB::table('data_imports')->where('id', $import->id)->update([
                        'workbook_metadata' => $this->decryptJson($import->workbook_metadata_encrypted),
                        'mapping_snapshot' => $this->decryptJson($import->mapping_snapshot_encrypted),
                    ]);
                }
            });

        DB::table('data_import_rows')
            ->select(['id', 'source_identifier_encrypted', 'issues_encrypted', 'result_encrypted'])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('data_import_rows')->where('id', $row->id)->update([
                        'source_identifier' => filled($row->source_identifier_encrypted) ? Crypt::decryptString($row->source_identifier_encrypted) : null,
                        'issues' => $this->decryptJson($row->issues_encrypted),
                        'result' => $this->decryptJson($row->result_encrypted),
                    ]);
                }
            });

        Schema::table('data_import_rows', function (Blueprint $table): void {
            $table->dropColumn(['source_identifier_encrypted', 'issues_encrypted', 'result_encrypted']);
        });

        Schema::table('data_imports', function (Blueprint $table): void {
            $table->dropColumn(['workbook_metadata_encrypted', 'mapping_snapshot_encrypted']);
        });
    }

    private function encryptJson(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $json = is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR);

        return Crypt::encryptString($json);
    }

    private function decryptJson(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return Crypt::decryptString($value);
    }
};
