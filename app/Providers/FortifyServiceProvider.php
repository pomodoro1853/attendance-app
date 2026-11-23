<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\LoginRequest;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Fortify のログインが受け取る LoginRequest を、日本語の文言を持つ自作のものに差し替える（FN008・FN009）
        $this->app->bind(FortifyLoginRequest::class, LoginRequest::class);

        // ログイン・ログアウトのあとの行き先を、権限や URL で分ける（US004・US005）
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        // 会員登録画面（US001）。提供ビュー views/user/register を使う
        Fortify::registerView(fn () => view('user.register'));

        // ログイン画面（US002）。提供ビュー views/user/user-login を使う
        Fortify::loginView(fn () => view('user.user-login'));

        // メール認証誘導画面（FN011）。提供ビュー views/auth/verify-email を使う
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));

        // ログインの判定（US002・US004）。ユーザーを返すと成功、null を返すと「ログイン情報が登録されていません」
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            // ユーザーがいない、またはパスワードが違う → 拒否
            if ($user === null || ! Hash::check($request->password, $user->password)) {
                return null;
            }

            // 管理者は /admin/login から、一般ユーザーは /login からだけ入れる（Q-021・Q-016）
            if ($user->admin_status !== $request->is('admin/login')) {
                return null;
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
