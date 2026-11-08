<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Concerns\LogsActivity;
use App\Models\Employee;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // SoftDeletes: a removed person's account is kept (and can be restored) but can't sign in.
    use HasApiTokens, HasFactory, LogsActivity, Notifiable, SoftDeletes;

    // Activity log: sign-in bookkeeping isn't an edit; passwords are logged as "changed" only.
    protected array $logIgnore = ['last_login_at', 'last_login_ip', 'email_verified_at'];
    protected array $logHidden = ['password'];

    public function activityLabel(): string
    {
        return $this->name;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }
    public function employee()
{
    return $this->hasOne(Employee::class);
}
public function isAdmin(): bool
{
    return $this->role === 'admin';
}

public function isHR(): bool
{
    return $this->role === 'hr';
}

public function isEmployee(): bool
{
    return $this->role === 'employee';
}


}
