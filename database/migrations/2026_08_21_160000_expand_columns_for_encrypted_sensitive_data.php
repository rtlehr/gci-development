<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel encryption expands short plaintext considerably. These
        // columns must be TEXT before encrypted values are written to them.
        Schema::table('addresses', function (Blueprint $table): void {
            $table->text('line_1')->change();
            $table->text('line_2')->nullable()->change();
            $table->text('city')->nullable()->change();
            $table->text('state')->nullable()->change();
            $table->text('postal_code')->nullable()->change();
            $table->text('country')->nullable()->change();
        });

        Schema::table('person_phone_numbers', function (Blueprint $table): void {
            $table->text('extension')->nullable()->change();
        });
    }

    public function down(): void
    {
        /*
         * Intentionally do not shrink encrypted columns during rollback.
         * Ciphertext may exceed the original VARCHAR sizes and truncating it
         * would make protected data unrecoverable. A rollback can safely leave
         * these schema widenings in place.
         */
    }
};
