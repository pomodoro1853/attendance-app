<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

// US008 勤怠詳細の表示（FN026。テストケース一覧 ID10）
class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private AttendanceRecord $record;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        // 休憩中の日を作るテストがあるので、今の時刻を 12:30 に固定する（parse(null) が 12:30 を出すと分かるように）
        $this->travelTo('2023-06-01 12:30:00');
        $this->user = User::factory()->create(['name' => 'テスト太郎']);
        $this->record = AttendanceRecord::factory()->for($this->user)->create(['date' => '2023-06-01']);
    }

    // 勤怠詳細を開く
    private function openDetail(): TestResponse
    {
        return $this->actingAs($this->user)->get('/attendance/'.$this->record->id);
    }

    // 「名前」が、ログインしている人の名前になっている
    public function test_name_is_login_user_name(): void
    {
        $this->openDetail()->assertOk()->assertSee('value="テスト太郎"', false);
    }

    // 「日付」が、一覧で選んだ日（2023年 6月1日）になっている
    public function test_date_is_selected_date(): void
    {
        $this->openDetail()
            ->assertSee('value="2023年"', false)
            ->assertSee('value="6月1日"', false);
    }

    // 「出勤・退勤」が、打刻した時刻と同じ
    public function test_clock_in_and_out_match_stamps(): void
    {
        $this->openDetail()
            ->assertSee('name="new_clock_in" value="09:00"', false)
            ->assertSee('name="new_clock_out" value="18:00"', false);
    }

    // 「休憩」が打刻と同じで、休憩の回数ぶんの行と、空の行が 1 つ出る（FN026-4）
    public function test_breaks_match_stamps_with_one_blank_row(): void
    {
        AttendanceBreak::factory()->for($this->record, 'attendanceRecord')->create();
        AttendanceBreak::factory()->for($this->record, 'attendanceRecord')->create(['break_in' => '15:00:00', 'break_out' => '15:15:00']);

        $this->openDetail()->assertSeeInOrder([
            'name="new_break_in[0]" value="12:00"',
            'name="new_break_out[0]" value="13:00"',
            'name="new_break_in[1]" value="15:00"',
            'name="new_break_out[1]" value="15:15"',
            'name="new_break_in[2]" value=""',
            'name="new_break_out[2]" value=""',
        ], false);
    }

    // 休憩中（まだ戻っていない）の休憩は、終わりの欄が空になる。今の時刻（12:30）が出てしまわないか見る
    public function test_unfinished_break_end_is_blank(): void
    {
        AttendanceBreak::factory()->for($this->record, 'attendanceRecord')->create(['break_out' => null]);

        $this->openDetail()
            ->assertSee('name="new_break_in[0]" value="12:00"', false)
            ->assertSee('name="new_break_out[0]" value=""', false);
    }

    // 他の人の勤怠の詳細は開けない（403）
    public function test_other_users_record_is_forbidden(): void
    {
        $otherRecord = AttendanceRecord::factory()->create(['date' => '2023-06-01']);

        $this->actingAs($this->user)->get('/attendance/'.$otherRecord->id)->assertForbidden();
    }
}
