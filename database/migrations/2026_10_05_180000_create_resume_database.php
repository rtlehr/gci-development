<?php

use App\Services\PermissionService;
use App\Services\Resumes\ResumeFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_formats', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('description')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->unique(['name', 'version']);
            $t->boolean('is_active')->default(true);
            $t->json('mappings');
            $t->json('column_orders')->nullable();
            $t->timestamps();
        });
        Schema::create('resumes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('person_id')->nullable()->constrained('people')->cascadeOnDelete();
            $t->foreignId('resume_format_id')->constrained()->restrictOnDelete();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->json('format_snapshot');
            $t->string('status')->default('draft')->index();
            $t->string('original_filename');
            $t->string('file_path');
            foreach (ResumeFields::SCALARS as $field) {
                $t->text($field)->nullable();
            }
            $t->longText('search_text')->nullable();
            $t->json('warnings')->nullable();
            $t->timestamps();
        });
        Schema::create('resume_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $t->string('section')->index();
            $t->unsignedInteger('sort_order');
            $t->json('data');
            $t->text('search_text');
            $t->timestamps();
        });
        DB::table('resume_formats')->insert(['name' => 'GCI Standard Resume', 'description' => 'Two-column DOCX table. Repeating entries use one paragraph per entry; education and certification use comma-separated columns. Review every import.', 'version' => 1, 'is_active' => true, 'mappings' => json_encode(ResumeFields::defaults()), 'column_orders' => json_encode(ResumeFields::REPEATING), 'created_at' => now(), 'updated_at' => now()]);
        foreach (['view_resumes' => 'Search and view saved resumes', 'manage_resumes' => 'Upload, review, edit and delete resumes', 'manage_resume_formats' => 'Create and version resume formats'] as $name => $label) {
            DB::table('permissions')->updateOrInsert(['name' => $name], ['group_name' => 'Resumes', 'label' => $label, 'description' => $label, 'is_system' => false, 'is_locked' => false, 'created_at' => now(), 'updated_at' => now()]);
            $permissionId = DB::table('permissions')->where('name', $name)->value('id');
            foreach (DB::table('roles')->whereIn('name', ['owner', 'admin'])->pluck('id') as $roleId) {
                DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }
        foreach (DB::table('users')->pluck('id') as $userId) {
            app(PermissionService::class)->clearUserPermissionCache($userId);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_entries');
        Schema::dropIfExists('resumes');
        Schema::dropIfExists('resume_formats');
    }
};
