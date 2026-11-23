<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

// 開発プロセス.md「ダミーデータ」の勤怠と休憩。全員の全レコードに 12:00〜13:00 の休憩を付ける
class AttendanceSeeder extends Seeder
{
    // user1 の当月 17 日の内訳。[出勤, 退勤, 日数]
    private const USER1_CURRENT_MONTH = [
        ['09:00', '18:00', 10], // 通常
        ['09:00', '20:00', 3], // 残業（10 時間・うち 2 時間が残業）
        ['09:30', '18:00', 2], // 遅刻
        ['09:00', '17:00', 1], // 早退
        ['08:00', '21:00', 1], // 長時間労働（12 時間・うち 4 時間が残業）
    ];

    public function run(): void
    {
        $this->seedUser1(User::where('email', 'user1@example.com')->first());

        // user2・user3 は「実運用に近い」データ。5 か月前の 1 日から昨日までの平日に、少しずつ違う時刻で出勤する
        $this->seedRealistic(User::where('email', 'user2@example.com')->first(), 0);
        $this->seedRealistic(User::where('email', 'user3@example.com')->first(), 3);
    }

    // user1: 過去 5 か月は各月の最初の平日 15 日を 9:00〜18:00、当月は最初の平日 17 日を内訳どおりに作る
    private function seedUser1(User $user): void
    {
        for ($monthsAgo = 5; $monthsAgo >= 1; $monthsAgo--) {
            $month = today()->startOfMonth()->subMonthsNoOverflow($monthsAgo);
            foreach ($this->weekdays($month, 15) as $date) {
                $this->createRecord($user, $date, '09:00', '18:00');
            }
        }

        $dates = $this->weekdays(today()->startOfMonth(), 17);
        foreach (self::USER1_CURRENT_MONTH as [$clockIn, $clockOut, $days]) {
            foreach (array_splice($dates, 0, $days) as $date) {
                $this->createRecord($user, $date, $clockIn, $clockOut);
            }
        }
    }

    // 平日だけを、出勤・退勤の時刻を少しずつ変えて作る。10 日に 1 日は休み（有給など）
    private function seedRealistic(User $user, int $offset): void
    {
        $clockIns = ['08:55', '09:00', '08:50', '09:05', '09:00'];
        $clockOuts = ['18:00', '18:15', '18:30', '18:00', '19:00'];
        $date = today()->startOfMonth()->subMonthsNoOverflow(5);
        $count = $offset;

        while ($date->lt(today())) {
            if ($date->isWeekday()) {
                if ($count % 10 !== 9) {
                    $this->createRecord($user, $date->toDateString(), $clockIns[$count % 5], $clockOuts[($count + 2) % 5]);
                }
                $count++;
            }
            $date->addDay();
        }
    }

    // その月の最初の平日を $count 日ぶん返す。今日は外す（ログインして、今日の打刻を試せるようにするため）
    private function weekdays(Carbon $month, int $count): array
    {
        $dates = [];
        $date = $month->copy();

        while (count($dates) < $count) {
            if ($date->isWeekday() && ! $date->isToday()) {
                $dates[] = $date->toDateString();
            }
            $date->addDay();
        }

        return $dates;
    }

    // 勤怠を 1 日ぶん作り、12:00〜13:00 の休憩を付ける
    private function createRecord(User $user, string $date, string $clockIn, string $clockOut): void
    {
        $record = new AttendanceRecord([
            'date' => $date,
            'clock_in' => $clockIn.':00',
            'clock_out' => $clockOut.':00',
        ]);
        // user_id は $fillable に入れていないので、直接入れてから保存する
        $record->user_id = $user->id;
        $record->save();

        $record->breaks()->create(['break_in' => '12:00:00', 'break_out' => '13:00:00']);
    }
}
