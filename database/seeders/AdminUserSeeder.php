<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 管理者ユーザーが存在しない場合のみ作成
        if (!User::where('email', 'admin@example.com')->exists()) {
            User::create([
                'name' => '管理者',
                'email' => 'admin@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department' => '管理部',
                'phone' => '03-1234-5678',
                'is_active' => true,
            ]);
        }

        // 営業担当者ユーザー
        if (!User::where('email', 'sales@example.com')->exists()) {
            User::create([
                'name' => '営業太郎',
                'email' => 'sales@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'sales',
                'department' => '営業部',
                'phone' => '03-1234-5679',
                'is_active' => true,
            ]);
        }

        // マネージャーユーザー
        if (!User::where('email', 'manager@example.com')->exists()) {
            User::create([
                'name' => '管理花子',
                'email' => 'manager@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'sales',
                'department' => '営業部',
                'phone' => '03-1234-5680',
                'is_active' => true,
            ]);
        }
    }
} 