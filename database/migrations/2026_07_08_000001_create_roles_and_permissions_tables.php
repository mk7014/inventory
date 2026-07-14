<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->boolean('is_system')->default(false); // system roles cannot be deleted
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();   // e.g. requisitions.create
            $table->string('module')->index();  // e.g. requisitions
            $table->string('action');           // e.g. create
            $table->string('label');            // human readable
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        // Seed the two base roles so existing users can be mapped before we drop
        // the legacy string column. The RolePermissionSeeder later attaches the
        // actual permissions to these same slugs (idempotent).
        $now = now();
        DB::table('roles')->insert([
            [
                'name' => 'Administrator', 'slug' => 'admin',
                'description' => 'Full, unrestricted access to every module.',
                'status' => 'active', 'is_system' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'Employee', 'slug' => 'employee',
                'description' => 'Limited access — personal balance and costing.',
                'status' => 'active', 'is_system' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        $roleIds = DB::table('roles')->pluck('id', 'slug');

        if (! Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable()->after('email')
                    ->constrained('roles')->nullOnDelete();
            });
        }

        // Backfill role_id from the legacy string column, then drop it.
        if (Schema::hasColumn('users', 'role')) {
            foreach ($roleIds as $slug => $id) {
                DB::table('users')->where('role', $slug)->update(['role_id' => $id]);
            }
            // Any unrecognised legacy value falls back to the employee role.
            DB::table('users')->whereNull('role_id')->update(['role_id' => $roleIds['employee']]);

            Schema::table('users', function (Blueprint $table) {
                // The legacy column was created with ->index(); SQLite leaves that
                // index behind on a drop-column and then rejects it as dangling,
                // so it has to go first.
                $table->dropIndex('users_role_index');
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('employee')->index()->after('email');
            });

            $roles = DB::table('roles')->pluck('slug', 'id');
            foreach ($roles as $id => $slug) {
                DB::table('users')->where('role_id', $id)->update(['role' => $slug]);
            }
        }

        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('role_id');
            });
        }

        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
