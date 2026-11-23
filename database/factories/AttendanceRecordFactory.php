<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    // 何も指定しなければ「今日、9:00〜18:00 の通常勤務」を作る
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => today()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => null,
        ];
    }
}
