<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

// 出勤登録画面（US006）
class AttendanceController extends Controller
{
    // 今の日時と、勤怠の状態を表示する。日付の形は US006 のテストで Figma と照らして確かめる
    public function index(Request $request): View
    {
        $now = now();

        return view('user.attendance-register', [
            'user' => $request->user(),
            'formattedDate' => $now->isoFormat('YYYY年M月D日(ddd)'),
            'formattedTime' => $now->format('H:i'),
        ]);
    }
}
