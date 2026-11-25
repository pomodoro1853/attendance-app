<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// 出勤登録画面（US006）
class AttendanceController extends Controller
{
    // 今の日時と、勤怠の状態を表示する（FN018・FN019）
    public function index(Request $request): View
    {
        $now = now();

        return view('user.attendance-register', [
            'user' => $request->user(),
            'formattedDate' => $now->isoFormat('YYYY年M月D日(ddd)'),
            'formattedTime' => $now->format('H:i'),
        ]);
    }

    // 押されたボタン（action）に応じて打刻する（FN020〜FN022）。今の状態で押せないボタンなら、何もしない
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $status = $user->attendance_status;
        // 今日の勤怠（勤務外なら null）
        $record = $user->attendanceRecords()->where('date', today()->toDateString())->first();
        $now = now()->format('H:i:s');

        // 押されたボタンと今の状態が合うときだけ打刻する。状態が「出勤中」「休憩中」なら、今日の勤怠は必ずある
        $action = $request->input('action');
        if ($action === 'clock_in' && $status === '勤務外') {
            $this->clockIn($user, $now);
        }
        if ($action === 'clock_out' && $status === '出勤中') {
            $record->update(['clock_out' => $now]);
        }
        if ($action === 'break_in' && $status === '出勤中') {
            $record->breaks()->create(['break_in' => $now]);
        }
        if ($action === 'break_out' && $status === '休憩中') {
            $record->breaks()->whereNull('break_out')->first()->update(['break_out' => $now]);
        }

        return redirect('/attendance');
    }

    // 出勤: 今日の勤怠を新しく作る。user_id は $fillable に入れていないので、直接入れてから保存する
    private function clockIn(User $user, string $time): void
    {
        $record = new AttendanceRecord([
            'date' => today()->toDateString(),
            'clock_in' => $time,
        ]);
        $record->user_id = $user->id;
        $record->save();
    }
}
