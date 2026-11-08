<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One line in the audit trail: who did what to which record, and when.
 * Written automatically by the LogsActivity trait and on every sign-in.
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null; // log lines are never edited

    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'description', 'changes', 'ip_address'];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    // The person who did it (still shown if they were later removed).
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    // Write a log line for the signed-in user (or the system when nobody is signed in).
    public static function record(string $action, string $description, ?Model $subject = null, ?array $changes = null): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => mb_strimwidth($description, 0, 255, '…'),
            'changes' => $changes ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
