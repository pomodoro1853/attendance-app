<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

// ID16 メール認証（FN011・FN012）
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    // 会員登録の入力一式
    private function validInput(): array
    {
        return [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }

    // 会員登録をする → 登録したユーザーに認証メールが送られる
    public function test_verification_email_is_sent_after_register(): void
    {
        Notification::fake();
        $this->post('/register', $this->validInput());

        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    // 会員登録したばかり（未認証）で勤怠登録画面を開く → メール認証誘導画面へ送られる
    public function test_unverified_user_is_redirected_to_verify_page(): void
    {
        Notification::fake();
        $this->post('/register', $this->validInput());

        $this->get('/attendance')->assertRedirect('/email/verify');
    }

    // メール認証誘導画面 → 「認証はこちらから」がメール認証サイト（Mailpit）へのリンクになっている
    public function test_verify_page_has_link_to_mail_site(): void
    {
        $this->withoutVite();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/email/verify')
            ->assertOk()
            ->assertSee('href="http://localhost:8025"', false);
    }

    // 「認証メールを再送する」を押す → 認証メールがもう一度送られる（FN012）
    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post('/email/verification-notification');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    // メールのリンクを開いて認証を完了する → 認証済みになり、勤怠登録画面へ行く
    public function test_user_is_redirected_to_attendance_after_verification(): void
    {
        $user = User::factory()->unverified()->create();
        // メールの中のリンクと同じ URL を作る（期限つきの署名が付いた URL）
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $response = $this->actingAs($user)->get($url);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect('/attendance?verified=1');
    }
}
