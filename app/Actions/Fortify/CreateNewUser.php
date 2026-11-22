<?php

namespace App\Actions\Fortify;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * 会員登録（US001）。入力チェックの決まりは RegisterRequest に書き、ここではそれを使うだけにする。
     * Fortify の登録コントローラーは FormRequest を受け取らないため（基本設計書 根拠メモ RegisterRequest）。
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $request = new RegisterRequest;
        Validator::make($input, $request->rules(), $request->messages())->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
