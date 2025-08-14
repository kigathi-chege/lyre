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
        Schema::dropIfExists('domains');
        Schema::dropIfExists('tenant_user_impersonation_tokens');
        Schema::dropIfExists('tenants');

        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                basic_fields($table, 'tenants');
                $table->json('data')->nullable();
            });
        }

        if (!Schema::hasColumn('tenants', 'name')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('name')->nullable()->after('id')->unique('name');
                $table->foreignIdFor(\App\Models\User::class);
            });
        }

        if (!Schema::hasColumn('tenants', 'slug')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });
        }

        if (!Schema::hasColumn('tenants', 'fqdn')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('fqdn')->nullable()->after('slug');
            });
        }

        if (!Schema::hasColumn('tenants', 'user_id')) {
            Schema::table('tenants', function (Blueprint $table) {
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
