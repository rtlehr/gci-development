<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataImportChange extends Model
{
    protected $fillable = ['data_import_id', 'data_import_row_id', 'sequence', 'model_type', 'model_id', 'action', 'before_payload', 'after_payload'];

    public function import(): BelongsTo { return $this->belongsTo(DataImport::class, 'data_import_id'); }
    public function row(): BelongsTo { return $this->belongsTo(DataImportRow::class, 'data_import_row_id'); }
}
