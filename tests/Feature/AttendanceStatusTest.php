<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * テストケース一覧 ID5「ステータス確認機能」のうち、User の attendance_status アクセサの部分。
 * 打刻画面（US006）ができたら、画面に表示されるかのテストをこのファイルに足す。
 */
class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    // 今日の勤怠が無い → 勤務外
    public function test_status_is_off_duty_without_todays_record(): void
    {
        $user = User::factory()->create();

        $this->assertSame('勤務外', $user->attendance_status);
    }

    // 昨日の勤怠しか無い → 勤務外（「今日」で絞れているかの確認）
    public function test_status_is_off_duty_with_only_yesterdays_record(): void
    {
        $user = User::factory()->create();
        $user->attendanceRecords()->create([
            'date' => today()->subDay()->toDateString(),
            'clock_in' => '09:00:00',
        ]);

        $this->assertSame('勤務外', $user->attendance_status);
    }

    // 出勤して、休憩していない → 出勤中
    public function test_status_is_working_after_clock_in(): void
    {
        $user = User::factory()->create();
        $user->attendanceRecords()->create([
            'date' => today()->toDateString(),
            'clock_in' => '09:00:00',
        ]);

        $this->assertSame('出勤中', $user->attendance_status);
    }

    // 休憩に入って、まだ戻っていない（break_out が無い） → 休憩中
    public function test_status_is_on_break_without_break_out(): void
    {
        $user = User::factory()->create();
        $record = $user->attendanceRecords()->create([
            'date' => today()->toDateString(),
            'clock_in' => '09:00:00',
        ]);

        $record->breaks()->create([
            'break_in' => '12:00:00',
        ]);

        $this->assertSame('休憩中', $user->attendance_status);
    }

    // 退勤した → 退勤済
    public function test_status_is_finished_after_clock_out(): void
    {
        $user = User::factory()->create();
        $user->attendanceRecords()->create([
            'date' => today()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->assertSame('退勤済', $user->attendance_status);
    }
}
