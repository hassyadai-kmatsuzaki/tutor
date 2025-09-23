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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name')->comment('顧客名');
            $table->enum('customer_type', [
                '法人', '個人', '自社', 'エンド法人', 'エンド（中国系）', 
                '飲食経営者', '不動明屋', '半法商事'
            ])->comment('顧客属性');
            $table->string('area_preference')->nullable()->comment('エリア希望');
            $table->set('property_type_preference', [
                '店舗', 'レジ', '土地', '事務所', '区分', '一棟ビル', '十地', '新築ホテル', 'オフィス'
            ])->nullable()->comment('種別希望');
            $table->text('detailed_requirements')->nullable()->comment('詳細要望');
            $table->bigInteger('budget_min')->nullable()->comment('予算下限（万円）');
            $table->bigInteger('budget_max')->nullable()->comment('予算上限（万円）');
            $table->decimal('yield_requirement', 5, 2)->nullable()->comment('利回り要求(%)');
            $table->string('contact_person')->nullable()->comment('担当者名');
            $table->string('phone', 20)->nullable()->comment('電話番号');
            $table->string('email')->nullable()->comment('メールアドレス');
            $table->text('address')->nullable()->comment('住所');
            $table->enum('priority', ['高', '中', '低'])->default('中')->comment('優先度');
            $table->enum('status', ['active', 'negotiating', 'closed', 'suspended'])
                  ->default('active')->comment('ステータス');
            $table->date('last_contact_date')->nullable()->comment('最終接触日');
            $table->date('next_contact_date')->nullable()->comment('次回接触予定日');
            $table->foreignId('assigned_to')->constrained('users')->comment('担当営業ID');
            $table->timestamps();

            // インデックス
            $table->index(['budget_min', 'budget_max', 'status'], 'idx_budget');
            $table->index(['assigned_to', 'status'], 'idx_assigned');
            $table->index(['priority', 'status'], 'idx_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
}; 