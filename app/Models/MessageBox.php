<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageBox extends Model
{
    protected $fillable = ['name','title','content_html','display_type','audience','audience_values','page_patterns','trigger_type','trigger_key','show_once','dismissible','priority','actions','form_fields','is_active','starts_at','ends_at','created_by','updated_by'];
    protected $casts = ['audience_values'=>'array','page_patterns'=>'array','actions'=>'array','form_fields'=>'array','show_once'=>'boolean','dismissible'=>'boolean','is_active'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime'];
}
