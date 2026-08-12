<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50);
            $table->string('name');
            $table->string('key');
            $table->string('field_type', 50);
            $table->text('description')->nullable();
            $table->string('placeholder')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['entity_type', 'key']);
            $table->index(['entity_type', 'is_active', 'sort_order']);
        });

        Schema::create('custom_field_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['custom_field_id', 'value']);
            $table->index(['custom_field_id', 'is_active', 'sort_order']);
        });

        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_id')->constrained()->cascadeOnDelete();
            $table->morphs('fieldable');
            $table->text('value_text')->nullable();
            $table->date('value_date')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['custom_field_id', 'fieldable_type', 'fieldable_id'], 'custom_field_values_unique');
        });

        $now = now();
        $permissions = [
            [
                'name' => 'access_custom_fields',
                'group_name' => 'Custom Fields',
                'label' => 'Access Custom Fields',
                'description' => 'Can view custom field configuration in Admin.',
                'is_system' => false,
                'is_locked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'manage_custom_fields',
                'group_name' => 'Custom Fields',
                'label' => 'Manage Custom Fields',
                'description' => 'Can create and maintain custom fields for people and positions.',
                'is_system' => false,
                'is_locked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                $permission,
            );
        }

        $roleIds = DB::table('roles')->whereIn('name', ['owner', 'admin'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('name', ['access_custom_fields', 'manage_custom_fields'])->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permission_role') && Schema::hasTable('permissions')) {
            $permissionIds = DB::table('permissions')->whereIn('name', ['access_custom_fields', 'manage_custom_fields'])->pluck('id');
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_field_options');
        Schema::dropIfExists('custom_fields');
    }
};
