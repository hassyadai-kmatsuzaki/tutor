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
        Schema::create('customer_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->enum('preference_type', [
                'area', 'station', 'structure', 'age', 'yield', 'size', 'other'
            ])->comment('条件種別');
            $table->string('preference_key', 100)->comment('条件キー');
            $table->text('preference_value')->comment('条件値');
            $table->enum('priority', ['must', 'want', 'nice_to_have'])
                  ->default('want')->comment('優先度');
            $table->timestamps();

            // インデックス
            $table->index(['customer_id', 'preference_type'], 'idx_customer_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_preferences');
    }
}; 