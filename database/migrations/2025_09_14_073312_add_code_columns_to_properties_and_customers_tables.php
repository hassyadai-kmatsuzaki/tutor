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
        Schema::table('properties', function (Blueprint $table) {
            $table->string('property_code')->nullable()->unique()->after('id');
            $table->index('property_code');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_code')->nullable()->unique()->after('id');
            $table->index('customer_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['property_code']);
            $table->dropColumn('property_code');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['customer_code']);
            $table->dropColumn('customer_code');
        });
    }
};
