<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app notification about a leave request (stored in the notifications table,
 * shown under the bell in the SPA).
 *
 * Events:
 *   submitted  → the department Head: someone asked for leave
 *   needs_hr   → HR & Admin: a request is ready for the final sign-off
 *   approved   → the employee
 *   rejected   → the employee (with the reason)
 *   cancelled  → whoever was due to act on it
 *
 * Email can be added later by returning ['database', 'mail'] from via() and adding toMail().
 */
class LeaveUpdate extends Notification
{
    use Queueable;

    public function __construct(
        public LeaveRequest $leave,
        public string $event,
        public ?string $actorName = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $leave = $this->leave;
        $who = $leave->employee?->user?->name ?? 'Someone';
        $days = $leave->days();
        $what = sprintf(
            '%d working day%s of %s leave (%s → %s)',
            $days,
            $days === 1 ? '' : 's',
            $leave->type,
            $leave->start_date->format('j M'),
            $leave->end_date->format('j M Y')
        );

        [$title, $message, $url] = match ($this->event) {
            'submitted' => ['New leave request', "{$who} asked for {$what}.", '/approvals'],
            'needs_hr' => ['Leave ready for sign-off', "{$who}'s request for {$what} is waiting for HR.", '/hr/leave?status=pending_hr'],
            'approved' => ['Leave approved', "Your request for {$what} was approved.", '/leave'],
            'rejected' => ['Leave rejected', "Your request for {$what} was rejected: {$leave->rejection_reason}", '/leave'],
            'cancelled' => ['Leave cancelled', "{$who} cancelled their request for {$what}.", '/leave'],
        };

        return [
            'event' => $this->event,
            'leave_request_id' => $leave->id,
            'title' => $title,
            'message' => $message,
            'url' => $url, // SPA path to open when clicked
            'actor' => $this->actorName,
        ];
    }
}
