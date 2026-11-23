<?php

use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Fortify が作るルート（/register・/login・/logout など）は、ここには書かない。
|
*/

// ロゴのリンク先「/」。ログインの状態と権限で行き先を分ける
Route::get('/', function () {
    if (! Auth::check()) {
        return redirect('/login');
    }

    return redirect(Auth::user()->admin_status ? '/admin/attendance/list' : '/attendance');
});

// 管理者のログイン・ログアウト（US004・US005）。Fortify は /admin の道を作らないので手で足す。
// 処理は一般ユーザーと同じ Fortify のコントローラー。管理者かどうかは authenticateUsing が URL で見分ける
Route::get('/admin/login', [AdminLoginController::class, 'create'])->middleware('guest');
Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->middleware(['guest', 'throttle:login']);
Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth');
