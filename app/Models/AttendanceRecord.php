<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'clock_in',
        'clock_out',
        'comment',
    ];

    protected function totalBreakTime(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatSeconds($this->breakSeconds()),
        );
    }

    private function breakSeconds(): int
    {
        $seconds = 0;

        foreach ($this->breaks as $break) {
            if ($break->break_out === null) {
                continue;
            }
            $seconds += Carbon::parse($break->break_in)->diffInSeconds(Carbon::parse($break->break_out));
        }

        return $seconds;
    }

    private function formatSeconds(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    protected function totalTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->clock_out === null) {
                    return null;
                }
                $workSeconds = Carbon::parse($this->clock_in)->diffInSeconds(Carbon::parse($this->clock_out));

                return $this->formatSeconds($workSeconds - $this->breakSeconds());
            },
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
