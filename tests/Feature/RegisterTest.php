<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * テストケース一覧 ID1「認証機能（一般ユーザー）」。US001 会員登録（FN001〜FN005）。
 * メッセージは機能要件一覧 FN003 の文言（一字一句そのまま。評価項目）。
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正しい入力一式を返す。テストごとに、確かめたい項目だけを $overrides で書き換える。
     */
    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    // 名前が未入力 → 「お名前を入力してください」
    public function test_name_is_required(): void
    {
        $response = $this->post('/register', $this->validInput(['name' => '']));

        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    // メールアドレスが未入力 → 「メールアドレスを入力してください」
    public function test_email_is_required(): void
    {
        $response = $this->post('/register', $this->validInput(['email' => '']));

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    // メールの形式ではない → 「メールアドレスはメール形式で入力してください」（FN003-d。テストケース一覧には無いが文言が評価項目）
    public function test_email_must_be_valid_format(): void
    {
        $response = $this->post('/register', $this->validInput(['email' => 'not-an-email']));

        $response->assertSessionHasErrors(['email' => 'メールアドレスはメール形式で入力してください']);
    }

    // 登録済みのメールアドレス → 「このメールアドレスは既に登録されています」（基本設計書 根拠メモ・Q-031）
    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post('/register', $this->validInput());

        $response->assertSessionHasErrors(['email' => 'このメールアドレスは既に登録されています']);
    }

    // パスワードが8文字未満 → 「パスワードは8文字以上で入力してください」
    public function test_password_must_be_at_least_8_characters(): void
    {
        $response = $this->post('/register', $this->validInput([
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]));

        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    // パスワードが未入力 → 「パスワードを入力してください」
    public function test_password_is_required(): void
    {
        $response = $this->post('/register', $this->validInput([
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    // パスワードと確認用が一致しない → 確認用の欄に「パスワードと一致しません」
    public function test_password_confirmation_must_match(): void
    {
        $response = $this->post('/register', $this->validInput([
            'password' => 'password',
            'password_confirmation' => 'wrongpassword',
        ]));

        $response->assertSessionHasErrors(['password_confirmation' => 'パスワードと一致しません']);
    }

    // 正しく入力した → users テーブルに保存される（パスワードは暗号化されて保存される）
    public function test_user_is_saved_with_valid_input(): void
    {
        $this->post('/register', $this->validInput());

        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
        ]);
        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    // 会員登録画面を開く → ログイン画面へのリンク（href="/login"）がある（FN004）
    public function test_register_page_has_link_to_login(): void
    {
        $this->withoutVite();

        $response = $this->get('/register');

        $response->assertOk();
        $response->assertSee('href="/login"', false);
    }
}
