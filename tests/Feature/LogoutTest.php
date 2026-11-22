<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// US003 ログアウト（FN013）
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    // ログイン中の一般ユーザーがログアウトする → ログインしていない状態になり、「/」へ行く
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        // ログインした状態にし、ログアウトの前はログインできていることを確かめる
        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
