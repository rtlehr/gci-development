<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('position_code')->unique(); // ZN-001
            $table->string('status')->default('open'); // open, filled, on_hold, closed
            $table->string('labor_category')->nullable();
            $table->string('job_title');
            $table->unsignedTinyInteger('level')->nullable(); // 1-5
            $table->string('project_team_name')->nullable();
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained('organizations')
                ->nullOnDelete();
            $table->string('customer_lead_name')->nullable();
            $table->date('customer_created_at')->nullable();
            $table->date('closed_at')->nullable();
            $table->text('closed_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
