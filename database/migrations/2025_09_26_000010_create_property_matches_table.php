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
        Schema::create('property_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('match_score', 5, 2)->default(0)->comment('マッチングスコア');
            $table->text('match_reason')->nullable()->comment('マッチング理由');
            $table->enum('status', ['matched', 'presented', 'interested', 'rejected', 'contracted'])->default('matched')->comment('ステータス');
            $table->timestamp('presented_at')->nullable()->comment('提案日時');
            $table->timestamp('response_at')->nullable()->comment('回答日時');
            $table->text('response_comment')->nullable()->comment('回答コメント');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('作成者');
            $table->timestamps();

            // ユニーク制約（同一物件×顧客は1つ）
            $table->unique(['property_id', 'customer_id'], 'uq_property_customer');

            // インデックス
            $table->index(['customer_id', 'match_score'], 'idx_customer_score');
            $table->index(['property_id', 'match_score'], 'idx_property_score');
            $table->index('status', 'idx_status');
            $table->index('presented_at', 'idx_presented_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_matches');
    }
};


