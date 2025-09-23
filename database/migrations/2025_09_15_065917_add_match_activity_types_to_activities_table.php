<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQLでENUMに値を追加
        DB::statement("ALTER TABLE activities MODIFY COLUMN activity_type ENUM(
            'property_created', 'property_updated', 'customer_created', 'customer_updated',
            'match_created', 'match_status_updated', 'match_note_added', 'presentation', 'contact', 'meeting', 'contract'
        ) COMMENT '活動種別'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 元のENUM値に戻す
        DB::statement("ALTER TABLE activities MODIFY COLUMN activity_type ENUM(
            'property_created', 'property_updated', 'customer_created', 'customer_updated',
            'match_created', 'presentation', 'contact', 'meeting', 'contract'
        ) COMMENT '活動種別'");
    }
};
