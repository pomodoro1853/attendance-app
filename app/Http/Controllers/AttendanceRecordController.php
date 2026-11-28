<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AttendanceRecord;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

// 勤怠一覧画面（US007）と勤怠詳細画面（US008）。一般ユーザー用
class AttendanceRecordController extends Controller
{
    // 勤怠詳細を出す（FN026）。無い id は 404、他の人の勤怠は 403
    public function show(Request $request, int $id): View
    {
        $record = AttendanceRecord::with('breaks')->findOrFail($id);
        abort_if($record->user_id !== $request->user()->id, 403);

        $date = Carbon::parse($record->date);

        return view('user.user-detail', [
            'user' => $request->user(),
            'data' => [
                'id' => $record->id,
                // 承認待ちの申請があれば、ビューは閲覧だけの表示に切り替わる（FN027）
                'application' => $record->applications()->where('approval_status', Application::STATUS_PENDING)->first(),
                'year' => $date->format('Y年'),
                'date' => $date->format('n月j日'),
                'clock_in' => Carbon::parse($record->clock_in)->format('H:i'),
                // 出勤中の日は退勤の時刻がまだ無い（一覧の buildRows と同じ守り）
                'clock_out' => $record->clock_out !== null ? Carbon::parse($record->clock_out)->format('H:i') : '',
                'breaks' => $this->formatBreaks($record),
                'comment' => $record->comment,
            ],
        ]);
    }

    // 休憩を、ビューの入力欄に入れる形（['break_in' => '12:00', 'break_out' => '13:00'] の並び）にする
    private function formatBreaks(AttendanceRecord $record): array
    {
        $breaks = [];
        foreach ($record->breaks as $break) {
            // 始まりは休憩入のボタンで必ず入る。終わりは休憩中だと null なので、parse(null)（今の時刻）を通さない
            $breaks[] = [
                'break_in' => Carbon::parse($break->break_in)->format('H:i'),
                'break_out' => $break->break_out !== null ? Carbon::parse($break->break_out)->format('H:i') : '',
            ];
        }

        return $breaks;
    }

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
            return Carbon::createFromFormat('Y-m-d', $date . '-01')->startOfDay();
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
