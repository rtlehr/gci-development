<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use JsonException;

class DataImport extends Model
{
    protected $fillable = [
        'uuid', 'status', 'original_filename', 'stored_path', 'worksheet', 'worksheet_index',
        'row_count', 'column_count', 'source_headers', 'workbook_metadata', 'mapping_snapshot',
        'validation_summary', 'error_summary', 'mapping_template_id', 'uploaded_by', 'started_at',
        'completed_at', 'rolled_back_at', 'rolled_back_by', 'created_count', 'updated_count',
        'skipped_count', 'failed_count',
    ];

    protected $hidden = [
        'workbook_metadata_encrypted',
        'mapping_snapshot_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'source_headers' => 'array',
            'validation_summary' => 'array',
            'error_summary' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }

    public function getWorkbookMetadataAttribute(mixed $legacyValue): ?array
    {
        return $this->encryptedJsonValue('workbook_metadata_encrypted', $legacyValue);
    }

    public function setWorkbookMetadataAttribute(mixed $value): void
    {
        $this->attributes['workbook_metadata_encrypted'] = $this->encryptJsonValue($value);
        $this->attributes['workbook_metadata'] = null;
    }

    public function getMappingSnapshotAttribute(mixed $legacyValue): ?array
    {
        return $this->encryptedJsonValue('mapping_snapshot_encrypted', $legacyValue);
    }

    public function setMappingSnapshotAttribute(mixed $value): void
    {
        $this->attributes['mapping_snapshot_encrypted'] = $this->encryptJsonValue($value);
        $this->attributes['mapping_snapshot'] = null;
    }

    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function mappingTemplate(): BelongsTo { return $this->belongsTo(DataImportMappingTemplate::class, 'mapping_template_id'); }
    public function rows(): HasMany { return $this->hasMany(DataImportRow::class); }
    public function changes(): HasMany { return $this->hasMany(DataImportChange::class); }

    private function encryptJsonValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR));
    }

    private function encryptedJsonValue(string $encryptedColumn, mixed $legacyValue): ?array
    {
        $encrypted = $this->attributes[$encryptedColumn] ?? null;

        if (filled($encrypted)) {
            try {
                return json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);
            } catch (DecryptException|JsonException) {
                return null;
            }
        }

        if ($legacyValue === null || $legacyValue === '') {
            return null;
        }

        if (is_array($legacyValue)) {
            return $legacyValue;
        }

        try {
            return json_decode((string) $legacyValue, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }
}
