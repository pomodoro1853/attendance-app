<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// US006 打刻（FN020 出勤・FN021 休憩・FN022 退勤。テストケース一覧 ID6〜ID8）
// 「勤怠一覧画面で確認できる」の項目は、勤怠一覧（US007・ID9）を作るときに足す
class AttendanceStampTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo('2023-06-01 08:00:00');
        $this->user = User::factory()->create();
    }

    // ボタンを押す（action を送る）
    private function stamp(string $action): void
    {
        $this->actingAs($this->user)->post('/attendance', ['action' => $action])->assertRedirect('/attendance');
    }

    // 勤務外で「出勤」ボタンがあり、押すと「出勤中」になり、出勤時刻が記録される
    public function test_clock_in_button_works(): void
    {
        $this->actingAs($this->user)->get('/attendance')->assertSee('value="clock_in"', false);

        $this->stamp('clock_in');

        $this->actingAs($this->user)->get('/attendance')->assertSee('出勤中');
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $this->user->id,
            'date' => '2023-06-01',
            'clock_in' => '08:00:00',
            'clock_out' => null,
        ]);
    }

    // 退勤済のとき「出勤」ボタンは出ない
    public function test_clock_in_button_is_hidden_after_clock_out(): void
    {
        AttendanceRecord::factory()->for($this->user)->create();

        $this->actingAs($this->user)->get('/attendance')->assertDontSee('value="clock_in"', false);
    }

    // 退勤済のあとに「出勤」を送っても、2回目の出勤は記録されない（1日に1回だけ）
    public function test_clock_in_is_only_once_per_day(): void
    {
        AttendanceRecord::factory()->for($this->user)->create();

        $this->stamp('clock_in');

        $this->assertSame(1, $this->user->attendanceRecords()->count());
    }

    // 出勤中で「休憩入」ボタンがあり、押すと「休憩中」になり、休憩の開始時刻が記録される
    public function test_break_in_button_works(): void
    {
        $this->stamp('clock_in');
        $this->actingAs($this->user)->get('/attendance')->assertSee('value="break_in"', false);

        $this->travelTo('2023-06-01 12:00:00');
        $this->stamp('break_in');

        $this->actingAs($this->user)->get('/attendance')->assertSee('休憩中');
        $this->assertDatabaseHas('breaks', ['break_in' => '12:00:00', 'break_out' => null]);
    }

    // 休憩入と休憩戻をしたあとも、「休憩入」ボタンがまた出る（1日に何回でも）
    public function test_break_in_can_be_done_many_times(): void
    {
        $this->stamp('clock_in');
        $this->stamp('break_in');
        $this->stamp('break_out');

        $this->actingAs($this->user)->get('/attendance')->assertSee('value="break_in"', false);
    }

    // 休憩中で「休憩戻」ボタンがあり、押すと「出勤中」に戻り、休憩の終了時刻が記録される
    public function test_break_out_button_works(): void
    {
        $this->stamp('clock_in');
        $this->travelTo('2023-06-01 12:00:00');
        $this->stamp('break_in');
        $this->actingAs($this->user)->get('/attendance')->assertSee('value="break_out"', false);

        $this->travelTo('2023-06-01 13:00:00');
        $this->stamp('break_out');

        $this->actingAs($this->user)->get('/attendance')->assertSee('出勤中');
        $this->assertDatabaseHas('breaks', ['break_in' => '12:00:00', 'break_out' => '13:00:00']);
    }

    // 2回目の休憩入のあとも「休憩戻」ボタンが出て、休憩は2件記録される（1日に何回でも）
    public function test_break_out_can_be_done_many_times(): void
    {
        $this->stamp('clock_in');
        $this->stamp('break_in');
        $this->stamp('break_out');
        $this->stamp('break_in');

        $this->actingAs($this->user)->get('/attendance')->assertSee('value="break_out"', false);
        $this->assertDatabaseCount('breaks', 2);
    }

    // 出勤中で「退勤」ボタンがあり、押すと「退勤済」と「お疲れ様でした。」が出て、退勤時刻が記録される
    public function test_clock_out_button_works(): void
    {
        $this->stamp('clock_in');
        $this->actingAs($this->user)->get('/attendance')->assertSee('value="clock_out"', false);

        $this->travelTo('2023-06-01 18:00:00');
        $this->stamp('clock_out');

        $this->actingAs($this->user)->get('/attendance')
            ->assertSee('退勤済')
            ->assertSee('お疲れ様でした。');
        $this->assertDatabaseHas('attendance_records', ['user_id' => $this->user->id, 'clock_out' => '18:00:00']);
    }

    // 勤務外のまま「休憩入」を送っても、何も記録されず、エラーにもならない
    public function test_break_in_is_ignored_when_off_duty(): void
    {
        $this->stamp('break_in');

        $this->assertDatabaseCount('breaks', 0);
        $this->assertDatabaseCount('attendance_records', 0);
    }
}
