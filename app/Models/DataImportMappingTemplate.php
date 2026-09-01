<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataImportMappingTemplate extends Model
{
    protected $fillable = ['name', 'description', 'mapping', 'source_headers', 'created_by', 'updated_by'];
    protected function casts(): array { return ['mapping' => 'array', 'source_headers' => 'array']; }
}
