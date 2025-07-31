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
        Schema::table('tenants', function (Blueprint $table) {
            $table->after('slug', function ($table) {
                $table->bigInteger('cr_number');
                $table->bigInteger('entity_number');
                $table->string('bank_name')->nullable();
                $table->string('bank_holder_name')->nullable();
                $table->string('iban')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'cr_number',
                'entity_number',
                'bank_name',
                'bank_holder_name',
                'iban',
            ]);
        });
    }
};
