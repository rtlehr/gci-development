<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_import_mapping_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('mapping')->nullable();
            $table->json('source_headers')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('data_imports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status', 40)->default('uploaded')->index();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('worksheet')->nullable();
            $table->unsignedInteger('worksheet_index')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('column_count')->default(0);
            $table->json('source_headers')->nullable();
            $table->json('workbook_metadata')->nullable();
            $table->json('mapping_snapshot')->nullable();
            $table->json('validation_summary')->nullable();
            $table->json('error_summary')->nullable();
            $table->foreignId('mapping_template_id')->nullable()->constrained('data_import_mapping_templates')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->foreignId('rolled_back_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();
        });

        Schema::create('data_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('data_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('source_row_number');
            $table->string('source_identifier')->nullable();
            $table->string('status', 40)->default('pending')->index();
            $table->string('action', 40)->nullable();
            $table->json('issues')->nullable();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained('candidates')->nullOnDelete();
            $table->json('result')->nullable();
            $table->timestamps();

            $table->unique(['data_import_id', 'source_row_number']);
        });

        Schema::create('data_import_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('data_import_id')->constrained()->cascadeOnDelete();
            $table->foreignId('data_import_row_id')->nullable()->constrained('data_import_rows')->nullOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('model_type');
            $table->string('model_id');
            $table->string('action', 20);
            $table->longText('before_payload')->nullable();
            $table->longText('after_payload')->nullable();
            $table->timestamps();

            $table->index(['data_import_id', 'sequence']);
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_import_changes');
        Schema::dropIfExists('data_import_rows');
        Schema::dropIfExists('data_imports');
        Schema::dropIfExists('data_import_mapping_templates');
    }
};
