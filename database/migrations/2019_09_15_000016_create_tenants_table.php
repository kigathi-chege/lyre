<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $prefix = config('lyre.table_prefix');
        $tableName = $prefix . 'tenants';

        Schema::dropIfExists('domains');
        Schema::dropIfExists('tenant_user_impersonation_tokens');
        Schema::dropIfExists('tenants');

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName, $prefix) {
                basic_fields($table, $tableName);
                $table->json('data')->nullable();
            });
        }

        if (!Schema::hasColumn($tableName, 'name')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('name')->nullable()->after('id')->unique('name');
                $table->foreignIdFor(\App\Models\User::class);
            });
        }

        if (!Schema::hasColumn($tableName, 'slug')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });
        }

        if (!Schema::hasColumn($tableName, 'fqdn')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('fqdn')->nullable()->after('slug');
            });
        }

        if (!Schema::hasColumn($tableName, 'user_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignIdFor(\App\Models\User::class);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
