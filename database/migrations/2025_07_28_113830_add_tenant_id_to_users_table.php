<?php

use App\Models\Tenant;
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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(Tenant::class)->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignIdFor(Tenant::class)->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignIdFor(Tenant::class)->after('id')->constrained()->cascadeOnDelete();
        });


        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignIdFor(Tenant::class)->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignIdFor(Tenant::class)->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignIdFor(Tenant::class)->after('id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });
    }
};
