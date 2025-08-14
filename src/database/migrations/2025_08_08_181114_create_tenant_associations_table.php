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
        Schema::create('tenant_associations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->morphs('tenantable');
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unique(
                ['tenantable_type', 'tenantable_id', 'tenant_id'],
                'tenant_associations_unique_tenantable_tenant_id'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_associations');
    }
};
