<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamToken extends Model
{
    protected $fillable = ['exam_id', 'student_id', 'token', 'status', 'expires_at', 'used_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function attempt(): HasOne
    {
        return $this->hasOne(ExamAttempt::class);
    }

    public function canBeUsed(): bool
    {
        return $this->status === 'unused' && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
