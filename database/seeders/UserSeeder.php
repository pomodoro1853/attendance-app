<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// 開発プロセス.md「ダミーデータ」のユーザー3人（全員メール認証済み。user3 は管理者）
class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([1, 2, 3] as $number) {
            $user = User::create([
                'name' => "ユーザー{$number}",
                'email' => "user{$number}@example.com",
                'password' => Hash::make('password'),
            ]);
            // $fillable に入れていない列は、直接入れてから保存する
            $user->email_verified_at = now();
            $user->admin_status = $number === 3;
            $user->save();
        }
    }
}
