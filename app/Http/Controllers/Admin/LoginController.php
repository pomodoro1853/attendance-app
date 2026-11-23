<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

// 管理者のログイン画面（US004）。ログインの処理そのものは Fortify が行う
class LoginController extends Controller
{
    public function create(): View
    {
        return view('admin.admin-login');
    }
}
