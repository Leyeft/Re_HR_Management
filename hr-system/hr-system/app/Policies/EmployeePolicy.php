<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * What separates Admin from HR: both manage staff, but only an Admin can
 * create, change or remove Admin accounts, or give someone the Admin role.
 * (Stops an HR user from promoting themselves.)
 */
class EmployeePolicy
{
    // Edit / delete / restore this person.
    public function manage(User $actor, Employee $employee): Response
    {
        return $employee->user?->role === 'admin' && $actor->role !== 'admin'
            ? Response::deny('Only an Admin can change or remove an Admin account.')
            : Response::allow();
    }

    // Create someone with, or change someone to, this system role.
    public function grantRole(User $actor, string $role): Response
    {
        return $role === 'admin' && $actor->role !== 'admin'
            ? Response::deny('Only an Admin can give someone the Admin role.')
            : Response::allow();
    }
}
