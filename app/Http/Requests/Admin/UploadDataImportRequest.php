<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadDataImportRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['file' => ['required', 'file', 'max:20480', 'mimes:xlsx']]; }
}
