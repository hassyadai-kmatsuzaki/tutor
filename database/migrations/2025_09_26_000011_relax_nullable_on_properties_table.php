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
            $table->string('property_name')->nullable()->change();
            $table->enum('property_type', [
                '店舗', 'レジ', '土地', '事務所', '区分', '一棟ビル', '十地', 'ホテル', '新築ホテル'
            ])->nullable()->change();
            $table->string('manager_name', 100)->nullable()->change();
            $table->date('registration_date')->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->string('information_source')->nullable()->change();
            $table->enum('transaction_category', ['先物', '元付', '売主'])->nullable()->change();
            $table->bigInteger('price')->nullable()->change();
            $table->string('prefecture', 50)->nullable()->change();
            $table->string('city', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('property_name')->nullable(false)->change();
            $table->enum('property_type', [
                '店舗', 'レジ', '土地', '事務所', '区分', '一棟ビル', '十地', 'ホテル', '新築ホテル'
            ])->nullable(false)->change();
            $table->string('manager_name', 100)->nullable(false)->change();
            $table->date('registration_date')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
            $table->string('information_source')->nullable()->change();
            $table->enum('transaction_category', ['先物', '元付', '売主'])->nullable(false)->change();
            $table->bigInteger('price')->nullable(false)->change();
            $table->string('prefecture', 50)->nullable(false)->change();
            $table->string('city', 100)->nullable(false)->change();
        });
    }
};


