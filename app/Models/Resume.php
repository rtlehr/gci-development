<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    protected $fillable = ['person_id', 'resume_format_id', 'uploaded_by', 'format_snapshot', 'status', 'original_filename', 'file_path', 'search_text', 'warnings', 'name', 'location', 'labor_category', 'requirement_id', 'tickets', 'other_information'];

    protected $hidden = ['file_path'];

    protected $casts = ['format_snapshot' => 'array', 'warnings' => 'array'];

    public function entries()
    {
        return $this->hasMany(ResumeEntry::class)->orderBy('sort_order');
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function format()
    {
        return $this->belongsTo(ResumeFormat::class, 'resume_format_id');
    }
}
