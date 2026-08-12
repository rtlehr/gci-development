<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')
                ->constrained('people')
                ->cascadeOnDelete();
            $table->foreignId('entered_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('entered_by_name');
            $table->string('category', 20);
            $table->text('note');
            $table->timestamps();

            $table->index(['person_id', 'category', 'created_at']);
        });

        DB::table('people')
            ->whereNotNull('notes')
            ->where('notes', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($people): void {
                foreach ($people as $person) {
                    DB::table('person_notes')->insert([
                        'person_id' => $person->id,
                        'entered_by_user_id' => null,
                        'entered_by_name' => 'Legacy record',
                        'category' => 'general',
                        'note' => $person->notes,
                        'created_at' => $person->updated_at ?? $person->created_at ?? now(),
                        'updated_at' => $person->updated_at ?? $person->created_at ?? now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_notes');
    }
};
