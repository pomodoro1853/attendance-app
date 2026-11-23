<?php

namespace Database\Factories;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceBreak>
 */
class AttendanceBreakFactory extends Factory
{
    // 何も指定しなければ「12:00〜13:00 の休憩」を作る
    public function definition(): array
    {
        return [
            'attendance_record_id' => AttendanceRecord::factory(),
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ];
    }
}
