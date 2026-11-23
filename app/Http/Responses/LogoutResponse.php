<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

// ログアウトのあとの行き先（US003・US005）。この時点ではもうログアウトしているので、権限ではなく URL で分ける
class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        return redirect($request->is('admin/logout') ? '/admin/login' : '/');
    }
}
