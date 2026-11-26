<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// US007 勤怠一覧（FN023 勤怠一覧・FN024 月の切り替え・FN025 詳細へ。テストケース一覧 ID9）
class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo('2023-06-15 10:00:00');
        $this->user = User::factory()->create();
    }

    // 自分の勤怠が、日付・出勤・退勤・休憩・合計の順に出る。他人の勤怠は出ない
    public function test_all_own_records_are_displayed(): void
    {
        $record = AttendanceRecord::factory()->for($this->user)->create(['date' => '2023-06-01']);
        AttendanceBreak::factory()->for($record, 'attendanceRecord')->create();
        AttendanceRecord::factory()->create(['date' => '2023-06-02', 'clock_in' => '07:45:00']);

        $this->actingAs($this->user)->get('/attendance/list')
            ->assertSeeInOrder(['06/01(木)', '09:00', '18:00', '1:00', '8:00'])
            ->assertDontSee('07:45');
    }

    // 出勤中（まだ退勤していない）の日は、退勤の欄が空になる。今の時刻（10:00）が出てしまわないか見る（FN023-2）
    public function test_clock_out_is_blank_while_on_duty(): void
    {
        AttendanceRecord::factory()->for($this->user)->create(['date' => '2023-06-15', 'clock_out' => null]);

        $this->actingAs($this->user)->get('/attendance/list')
            ->assertSeeInOrder(['06/15(木)', '09:00'])
            ->assertDontSee('10:00');
    }

    // 開いたときは、今の月が出る
    public function test_current_month_is_displayed(): void
    {
        $this->actingAs($this->user)->get('/attendance/list')->assertSee('2023/06');
    }

    // 「前月」のリンク先を開くと、前の月の勤怠が出る
    public function test_previous_month_is_displayed(): void
    {
        AttendanceRecord::factory()->for($this->user)->create(['date' => '2023-05-10', 'clock_in' => '09:15:00']);

        $this->actingAs($this->user)->get('/attendance/list')->assertSee('href="?date=2023-05"', false);

        $this->actingAs($this->user)->get('/attendance/list?date=2023-05')
            ->assertSee('2023/05')
            ->assertSeeInOrder(['05/10(水)', '09:15']);
    }

    // 「翌月」のリンク先を開くと、次の月の勤怠が出る
    public function test_next_month_is_displayed(): void
    {
        AttendanceRecord::factory()->for($this->user)->create(['date' => '2023-07-03', 'clock_in' => '09:20:00']);

        $this->actingAs($this->user)->get('/attendance/list')->assertSee('href="?date=2023-07"', false);

        $this->actingAs($this->user)->get('/attendance/list?date=2023-07')
            ->assertSee('2023/07')
            ->assertSeeInOrder(['07/03(月)', '09:20']);
    }

    // 「詳細」のリンクが、その日の勤怠詳細（/attendance/{id}）を指す。詳細画面そのものは US008 で作る
    public function test_detail_link_points_to_the_day(): void
    {
        $record = AttendanceRecord::factory()->for($this->user)->create(['date' => '2023-06-01']);

        $this->actingAs($this->user)->get('/attendance/list')
            ->assertSee('href="'.url('/attendance/'.$record->id).'"', false);
    }
}
