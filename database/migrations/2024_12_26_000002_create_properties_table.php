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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('property_name')->comment('物件名');
            $table->enum('property_type', [
                '店舗', 'レジ', '土地', '事務所', '区分', '一棟ビル', '十地', '新築ホテル'
            ])->comment('種別');
            $table->string('manager_name', 100)->comment('担当者名');
            $table->date('registration_date')->comment('登録日');
            $table->text('address')->comment('住所');
            $table->string('information_source')->nullable()->comment('情報取得先');
            $table->enum('transaction_category', ['先物', '元付', '売主'])->comment('取引区分');
            $table->decimal('land_area', 10, 2)->nullable()->comment('地積(㎡)');
            $table->decimal('building_area', 10, 2)->nullable()->comment('建物面積(㎡)');
            $table->string('structure_floors', 100)->nullable()->comment('構造階数');
            $table->date('construction_year')->nullable()->comment('築年');
            $table->bigInteger('price')->comment('価格（万円）');
            $table->decimal('price_per_unit', 10, 2)->nullable()->comment('坪単価（万円）');
            $table->decimal('current_profit', 15, 2)->nullable()->comment('現況利回り(%)');
            $table->string('prefecture', 50)->comment('都道府県');
            $table->string('city', 100)->comment('市区町村');
            $table->string('nearest_station', 100)->nullable()->comment('最寄り駅');
            $table->integer('walking_minutes')->nullable()->comment('徒歩分数');
            $table->text('remarks')->nullable()->comment('備考');
            $table->enum('status', ['available', 'reserved', 'sold', 'suspended'])
                  ->default('available')->comment('ステータス');
            $table->foreignId('created_by')->constrained('users')->comment('登録者ID');
            $table->timestamps();

            // インデックス
            $table->index(['property_type', 'prefecture', 'city', 'price', 'status'], 'idx_search');
            $table->index(['price', 'status'], 'idx_price_range');
            $table->index(['land_area', 'building_area'], 'idx_area');
            $table->index(['current_profit', 'status'], 'idx_yield');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
}; 