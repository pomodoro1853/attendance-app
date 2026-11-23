<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

// ログインのあとの行き先（US002・US004）
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        return redirect($request->user()->admin_status === true ? '/admin/attendance/list' : '/attendance');
    }
}
