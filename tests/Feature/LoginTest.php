<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID2「ログイン認証機能（一般ユーザー）」。US002 ログイン（FN006〜FN010）。
 * 管理者を拒否するテストは Q-021（A: 拒否して「ログイン情報が登録されていません」）による。
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインに使う一般ユーザーを1人作る（メール認証は済み。パスワードは password123）。
     */
    private function createUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'test@example.com',
            'password' => 'password123',
        ], $overrides));
    }

    // メールアドレスが未入力 → 「メールアドレスを入力してください」
    public function test_email_is_required(): void
    {
        $this->createUser();

        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
        $this->assertGuest();
    }

    // パスワードが未入力 → 「パスワードを入力してください」
    public function test_password_is_required(): void
    {
        $this->createUser();

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    // 登録と違うメールアドレス → 「ログイン情報が登録されていません」（認証の失敗。入力チェックではない）
    public function test_login_fails_with_wrong_email(): void
    {
        $this->createUser();

        $response = $this->post('/login', [
            'email' => 'wrongtest@example.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }

    // 正しく入力した → ログインでき、打刻画面（/attendance）へ行く
    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = $this->createUser();

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/attendance');
    }

    // 管理者が一般ユーザーのログイン画面から入ろうとした → 拒否して「ログイン情報が登録されていません」（Q-021）
    public function test_admin_cannot_login_from_user_login_page(): void
    {
        $this->createUser(['admin_status' => true]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
        $this->assertGuest();
    }

    // ログイン画面を開く → 会員登録画面へのリンク（href="/register"）がある（FN010）
    public function test_login_page_has_link_to_register(): void
    {
        $this->withoutVite();

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('href="/register"', false);
    }
}
