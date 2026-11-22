<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 会員登録の入力チェック（US001・FN002・FN003）。ルールは基本設計書 §バリデーション、文言は FN003 と根拠メモのとおり。
 */
class RegisterRequest extends FormRequest
{
    /**
     * 会員登録は誰でもできる（ログイン前の画面）ので、常に許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 入力欄ごとの決まり（基本設計書 §バリデーション RegisterRequest）。
     */
    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8',
            'password_confirmation' => 'same:password',
        ];
    }

    /**
     * エラーの文（FN003 の a〜f と、基本設計書 根拠メモの3つ）。一字一句このまま（評価項目）。
     */
    public function messages(): array
    {
        return [
            'name.required' => 'お名前を入力してください',
            'name.max' => 'お名前は255文字以内で入力してください',
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',
            'email.max' => 'メールアドレスは255文字以内で入力してください',
            'email.unique' => 'このメールアドレスは既に登録されています',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは8文字以上で入力してください',
            'password_confirmation.same' => 'パスワードと一致しません',
        ];
    }
}
