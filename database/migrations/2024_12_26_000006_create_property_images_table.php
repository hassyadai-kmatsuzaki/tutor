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
        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->string('image_path', 500)->comment('画像パス');
            $table->enum('image_type', ['exterior', 'interior', 'layout', 'other'])
                  ->default('other')->comment('画像種別');
            $table->string('caption')->nullable()->comment('キャプション');
            $table->integer('sort_order')->default(0)->comment('表示順');
            $table->timestamps();

            // インデックス
            $table->index(['property_id', 'sort_order'], 'idx_property_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_images');
    }
}; 