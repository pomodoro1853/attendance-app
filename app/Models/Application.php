<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    const STATUS_PENDING = 1;

    const STATUS_APPROVED = 2;

    protected $fillable = [
        'approval_status',
        'new_clock_in',
        'new_clock_out',
        'new_date',
        'comment',
    ];

    protected $casts = [
        'new_date' => 'date',
    ];

    protected function approvalStatus(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => [
                self::STATUS_PENDING => '承認待ち',
                self::STATUS_APPROVED => '承認済み',
            ][$value] ?? '不明',
        );
    }

    protected function applicationDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->created_at->format('Y/m/d');
            },
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function proposalBreaks()
    {
        return $this->hasMany(ProposalBreak::class);
    }
}
