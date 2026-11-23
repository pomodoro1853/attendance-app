<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// US006 出勤登録画面の日時（FN018・ID4）とステータス（FN019・ID5）
class AttendanceScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        // 「今」を 2023年6月1日(木) 08:00 に固定する。Figma の画面と同じ日時
        $this->travelTo('2023-06-01 08:00:00');
    }

    // 今の日時が、Figma と同じ形（2023年6月1日(木)・08:00）で表示される
    public function test_current_datetime_is_displayed_in_ui_format(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/attendance')
            ->assertOk()
            ->assertSee('2023年6月1日(木)')
            ->assertSee('08:00');
    }

    // 今日の勤怠が無い → 「勤務外」と表示される
    public function test_status_off_duty_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/attendance')->assertSee('勤務外');
    }

    // 今日出勤して、まだ退勤していない → 「出勤中」と表示される
    public function test_status_working_is_displayed(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create(['clock_out' => null]);

        $this->actingAs($user)->get('/attendance')->assertSee('出勤中');
    }

    // 今日休憩に入って、まだ戻っていない → 「休憩中」と表示される
    public function test_status_on_break_is_displayed(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create([
            'clock_out' => null,
        ]);
        AttendanceBreak::factory()->for($record)->create([
            'break_out' => null,
        ]);

        $this->actingAs($user)->get('/attendance')->assertSee('休憩中');
    }

    // 今日退勤した → 「退勤済」と表示される
    public function test_status_finished_is_displayed(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create();

        $this->actingAs($user)->get('/attendance')->assertSee('退勤済');
    }
}
