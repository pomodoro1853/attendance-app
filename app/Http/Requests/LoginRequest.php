<?php

namespace App\Http\Requests;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

/**
 * ログインの入力チェック（US002・US004 で共用。FN008・FN009・FN015・FN016）。
 * Fortify の LoginRequest を継承し、FortifyServiceProvider で差し替えて使う。
 * email の形式ルールは付けない（基本設計書 根拠メモ LoginRequest）。
 */
class LoginRequest extends FortifyLoginRequest
{
    /**
     * 入力欄ごとの決まり（基本設計書 §バリデーション LoginRequest）。
     */
    public function rules(): array
    {
        return [
            'email' => 'required',
            'password' => 'required',
        ];
    }

    /**
     * エラーの文（FN009 の a・b）。一字一句このまま（評価項目）。
     */
    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',
        ];
    }
}
