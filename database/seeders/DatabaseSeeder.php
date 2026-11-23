<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 開発プロセス.md「ダミーデータ」。ユーザーを先に作り、そのユーザーに勤怠を付ける
        $this->call([
            UserSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}
