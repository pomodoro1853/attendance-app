<?php

namespace App\Http\Controllers;

use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

// 勤怠一覧画面（一般ユーザー。US007）
class AttendanceRecordController extends Controller
{
    // 表示する月の自分の勤怠を、1日1行で出す（FN023・FN024）
    public function index(Request $request): View
    {
        $month = $this->targetMonth($request->query('date'));

        // その月の自分の勤怠。休憩も一緒に読み込み、合計の計算で1行ごとに SQL が走るのを防ぐ（N+1 対策）
        // keyBy('date') で「'2023-06-01' => その日の勤怠」の形にして、日付から探せるようにする
        $records = $request->user()->attendanceRecords()
            ->with('breaks')
            ->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->get()
            ->keyBy('date');

        return view('user.user-attendance-list', [
            'date' => $month,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'formattedAttendanceRecords' => $this->buildRows($month, $records),
        ]);
    }

    // ?date=2023-06 の形なら、その月の1日。無い・形が違うときは今月の1日（形が違っても 500 にしないため）
    private function targetMonth(?string $date): Carbon
    {
        if ($date !== null && preg_match('/^\d{4}-\d{2}$/', $date) === 1) {
            return Carbon::createFromFormat('Y-m-d', $date.'-01')->startOfDay();
        }

        return today()->startOfMonth();
    }

    // 月の1日〜末日を1行ずつ作る。勤怠の無い日は日付だけ入れて、ほかは空にする（FN023-2）
    private function buildRows(Carbon $month, Collection $records): array
    {
        $rows = [];
        foreach (CarbonPeriod::create($month, $month->copy()->endOfMonth()) as $day) {
            // その日の勤怠（無い日は null）
            $record = $records->get($day->toDateString());
            $rows[] = [
                'id' => $record?->id,
                'date' => $day->isoFormat('MM/DD(ddd)'),
                // 勤怠があれば出勤の時刻は必ずある。無い日に parse(null) を通すと「今の時刻」が出てしまうので、先に分ける
                'clock_in' => $record !== null ? Carbon::parse($record->clock_in)->format('H:i') : '',
                // 出勤中（まだ退勤していない）の日は、勤怠があっても退勤の時刻が null なので、時刻そのものを確かめる
                'clock_out' => $record?->clock_out !== null ? Carbon::parse($record->clock_out)->format('H:i') : '',
                'total_break_time' => $record?->total_break_time,
                'total_time' => $record?->total_time,
            ];
        }

        return $rows;
    }
}
