<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A login account.
 *
 * - role: the system role — 'employee' (default), 'hr' or 'admin'. Decides what the person
 *   can do in the app (see EnsureRole middleware and EmployeePolicy).
 * - Most accounts have an Employee record (department, rank, leave); Admin/HR accounts may not.
 * - HasApiTokens: Sanctum tokens for API clients (Postman); the SPA uses the session cookie.
 * - Notifiable: receives in-app notifications (the bell).
 * - SoftDeletes: a removed person's account is kept (can be restored) but can't sign in.
 * - LogsActivity: changes are written to the activity log.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, LogsActivity, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    // Never sent to the browser (UserResource also lists only safe fields).
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Activity log: sign-in bookkeeping isn't an edit; passwords are logged as "changed" only.
    protected array $logIgnore = ['last_login_at', 'last_login_ip', 'email_verified_at'];
    protected array $logHidden = ['password'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // hashed (bcrypt) automatically when set
            'last_login_at' => 'datetime',
        ];
    }

    // How this account is named in the activity log.
    public function activityLabel(): string
    {
        return $this->name;
    }

    // The person's employee record (department, rank, leave). Null for some Admin/HR accounts.
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }
}
