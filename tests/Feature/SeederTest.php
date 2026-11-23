<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// 開発プロセス.md「ダミーデータ」が、書かれた予測値どおりに作れるか
class SeederTest extends TestCase
{
    use RefreshDatabase;

    // "H:i" の文字（例 "08:00"）を分に直す
    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return (int) $hours * 60 + (int) $minutes;
    }

    // 一般ユーザー2人と管理者1人ができ、全員メール認証済み
    public function test_three_users_are_created(): void
    {
        $this->seed();

        $this->assertSame(3, User::count());
        $this->assertFalse(User::where('email', 'user1@example.com')->first()->admin_status);
        $this->assertFalse(User::where('email', 'user2@example.com')->first()->admin_status);
        $this->assertTrue(User::where('email', 'user3@example.com')->first()->admin_status);
        $this->assertSame(0, User::whereNull('email_verified_at')->count());
    }

    // 全員に勤怠があり、休憩の無い勤怠は無い
    public function test_every_user_has_attendance_with_breaks(): void
    {
        $this->seed();

        foreach (User::all() as $user) {
            $this->assertGreaterThan(0, $user->attendanceRecords()->count(), $user->email);
        }
        $this->assertSame(0, AttendanceRecord::doesntHave('breaks')->count());
    }

    // user1: 過去 6 か月で 92 日・総労働 744 時間・総残業 10 時間（1 日 8 時間を超えた分）
    public function test_user1_totals_match_expected_report(): void
    {
        $this->seed();
        $records = User::where('email', 'user1@example.com')->first()->attendanceRecords()->with('breaks')->get();

        $workMinutes = $records->sum(fn ($record) => $this->toMinutes($record->total_time));
        $overtimeMinutes = $records->sum(fn ($record) => max(0, $this->toMinutes($record->total_time) - 8 * 60));

        $this->assertSame(92, $records->count());
        $this->assertSame(744 * 60, $workMinutes);
        $this->assertSame(10 * 60, $overtimeMinutes);
    }

    // user1 の当月: 17 日・遅刻 2 回・早退 1 回・長時間労働（10 時間超）1 日
    public function test_user1_current_month_counts_match_expected_report(): void
    {
        $this->seed();
        $records = User::where('email', 'user1@example.com')->first()->attendanceRecords()->with('breaks')
            ->whereBetween('date', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])
            ->get();

        $this->assertSame(17, $records->count());
        $this->assertSame(2, $records->filter(fn ($record) => $record->clock_in > '09:00:00')->count());
        $this->assertSame(1, $records->filter(fn ($record) => $record->clock_out < '18:00:00')->count());
        $this->assertSame(1, $records->filter(fn ($record) => $this->toMinutes($record->total_time) > 10 * 60)->count());
    }
}
