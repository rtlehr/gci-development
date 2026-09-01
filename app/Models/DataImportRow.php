<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use JsonException;

class DataImportRow extends Model
{
    protected $fillable = [
        'data_import_id', 'source_row_number', 'source_identifier', 'status', 'action', 'issues',
        'person_id', 'position_id', 'candidate_id', 'result',
    ];

    protected $hidden = [
        'source_identifier_encrypted',
        'issues_encrypted',
        'result_encrypted',
    ];


    public function getSourceIdentifierAttribute(mixed $legacyValue): ?string
    {
        $encrypted = $this->attributes['source_identifier_encrypted'] ?? null;

        if (filled($encrypted)) {
            try {
                return Crypt::decryptString($encrypted);
            } catch (DecryptException) {
                return null;
            }
        }

        return filled($legacyValue) ? (string) $legacyValue : null;
    }

    public function setSourceIdentifierAttribute(mixed $value): void
    {
        $this->attributes['source_identifier_encrypted'] = filled($value)
            ? Crypt::encryptString((string) $value)
            : null;
        $this->attributes['source_identifier'] = null;
    }

    public function getIssuesAttribute(mixed $legacyValue): ?array
    {
        return $this->encryptedJsonValue('issues_encrypted', $legacyValue);
    }

    public function setIssuesAttribute(mixed $value): void
    {
        $this->attributes['issues_encrypted'] = $this->encryptJsonValue($value);
        $this->attributes['issues'] = null;
    }

    public function getResultAttribute(mixed $legacyValue): ?array
    {
        return $this->encryptedJsonValue('result_encrypted', $legacyValue);
    }

    public function setResultAttribute(mixed $value): void
    {
        $this->attributes['result_encrypted'] = $this->encryptJsonValue($value);
        $this->attributes['result'] = null;
    }

    public function dataImport(): BelongsTo { return $this->belongsTo(DataImport::class); }
    public function person(): BelongsTo { return $this->belongsTo(Person::class); }
    public function position(): BelongsTo { return $this->belongsTo(Position::class); }
    public function candidate(): BelongsTo { return $this->belongsTo(Candidate::class); }

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
