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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->comment('実行者ID');
            $table->enum('activity_type', [
                'property_created', 'property_updated', 'customer_created', 'customer_updated',
                'match_created', 'match_status_updated', 'match_note_added', 'presentation', 'contact', 'meeting', 'contract'
            ])->comment('活動種別');
            $table->enum('subject_type', ['property', 'customer', 'match'])->comment('対象種別');
            $table->bigInteger('subject_id')->comment('対象ID');
            $table->string('title')->comment('タイトル');
            $table->text('description')->nullable()->comment('詳細');
            $table->timestamp('activity_date')->comment('活動日時');
            $table->timestamps();

            // インデックス
            $table->index(['subject_type', 'subject_id'], 'idx_subject');
            $table->index('activity_date', 'idx_activity_date');
            $table->index(['user_id', 'activity_date'], 'idx_user_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
}; 