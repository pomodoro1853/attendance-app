<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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
