<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * What separates Admin from HR: both manage staff, but only an Admin can
 * create, change or remove Admin accounts, or give someone the Admin role.
 * (Stops an HR user from promoting themselves.)
 *
 * Salary: seen by Admin, HR and the person themselves (not Heads or colleagues);
 * changed only by Admin/HR, and never by the person themselves.
 */
class EmployeePolicy
{
    // See this person's salary. Checked per employee in EmployeeResource, so the
    // field is left out of the JSON entirely for anyone else.
    public function viewSalary(User $actor, Employee $employee): bool
    {
        return in_array($actor->role, ['admin', 'hr'], true) || $actor->id === $employee->user_id;
    }

    // Change this person's salary.
    public function editSalary(User $actor, Employee $employee): Response
    {
        if (! in_array($actor->role, ['admin', 'hr'], true)) {
            return Response::deny('Only HR or an Admin can change salaries.');
        }
        if ($actor->id === $employee->user_id) {
            return Response::deny('You can\'t change your own salary. Ask another HR member or an Admin.');
        }

        return $this->manage($actor, $employee);
    }

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
