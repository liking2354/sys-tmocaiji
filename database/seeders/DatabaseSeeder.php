<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 创建管理员用户
        User::create([
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'email' => 'admin@example.com',
            'status' => 1,
        ]);
        
        // 运行采集组件种子
        $this->call([
            CollectorSeeder::class,
            PhpAppCollectorSeeder::class,
        ]);
    }
}