<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// US004 管理者のログイン（FN014〜FN016）・US005 管理者のログアウト（FN017）
class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    // テスト用の管理者を作る（パスワードは password123）
    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->admin_status = true;
        $admin->save();

        return $admin;
    }

    // 管理者のログイン画面が開く
    public function test_admin_login_page_is_displayed(): void
    {
        $this->withoutVite();

        $this->get('/admin/login')->assertOk()->assertSee('action="/admin/login"', false);
    }

    // メールアドレスが未入力 → 「メールアドレスを入力してください」
    public function test_email_is_required(): void
    {
        $this->createAdmin();

        $response = $this->post('/admin/login', ['email' => '', 'password' => 'password123']);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    // パスワードが未入力 → 「パスワードを入力してください」
    public function test_password_is_required(): void
    {
        $this->createAdmin();

        $response = $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => '']);

        $this->assertGuest();
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    // メールアドレスが違う → 「ログイン情報が登録されていません」
    public function test_login_fails_with_wrong_email(): void
    {
        $this->createAdmin();

        $response = $this->post('/admin/login', ['email' => 'wrong@example.com', 'password' => 'password123']);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }

    // 正しく入力した管理者 → ログインでき、管理者の勤怠一覧画面へ行く
    public function test_admin_can_login_with_correct_credentials(): void
    {
        $admin = $this->createAdmin();

        $response = $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password123']);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect('/admin/attendance/list');
    }

    // 一般ユーザーが管理者のログイン画面から入ろうとした → 拒否して「ログイン情報が登録されていません」
    public function test_user_cannot_login_from_admin_login_page(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => bcrypt('password123')]);

        $response = $this->post('/admin/login', ['email' => 'user@example.com', 'password' => 'password123']);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }

    // 管理者がログアウトする → ログインしていない状態になり、管理者のログイン画面へ行く
    public function test_admin_can_logout(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin);
        $this->assertAuthenticatedAs($admin);

        $response = $this->post('/admin/logout');

        $this->assertGuest();
        $response->assertRedirect('/admin/login');
    }
}
