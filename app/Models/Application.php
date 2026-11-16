<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_status',
        'new_clock_in',
        'new_clock_out',
        'new_date',
        'comment',
    ];

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
