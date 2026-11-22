<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// ロゴのリンク先「/」の行き先（ログアウト後もここに着く）
class RootRedirectTest extends TestCase
{
    use RefreshDatabase;

    // ログインしていない → ログイン画面へ
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    // 一般ユーザー → 出勤登録画面へ
    public function test_user_is_redirected_to_attendance(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect('/attendance');
    }

    // 管理者 → 管理者の勤怠一覧画面へ
    public function test_admin_is_redirected_to_admin_attendance_list(): void
    {
        $admin = User::factory()->create();
        $admin->admin_status = true;
        $admin->save();

        $this->actingAs($admin)->get('/')->assertRedirect('/admin/attendance/list');
    }
}
