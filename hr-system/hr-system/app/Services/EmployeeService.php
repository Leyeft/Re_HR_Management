<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creating, updating and deleting staff, with the safeguards that keep the
 * system usable: there is always an Admin, always an HR, and a department with
 * staff always has a Head to approve their leave.
 */
class EmployeeService
{
    /**
     * $data: name, email, password, role, rank, department_id, date_of_birth, date_hired
     */
    public function create(array $data): Employee
    {
        $this->ensureHeadIsEmployeeRole($data['role'], $data['rank']);

        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
            ]);

            $employee = Employee::create([
                'user_id' => $user->id,
                'department_id' => $data['department_id'],
                'employee_code' => Employee::generateCode((int) $data['department_id']),
                'rank' => $data['rank'],
                'date_of_birth' => $data['date_of_birth'],
                'date_hired' => $data['date_hired'],
            ]);

            if (array_key_exists('skills', $data)) {
                $this->syncSkills($employee, $data['skills'] ?? []);
            }

            return $employee;
        });
    }

    /**
     * $data: name, email, role, rank, department_id, date_of_birth, date_hired
     */
    public function update(Employee $employee, array $data): Employee
    {
        $user = $employee->user;

        $this->ensureHeadIsEmployeeRole($data['role'], $data['rank']);

        // A role change must not remove the last Admin or the last HR.
        foreach (['admin' => 'Admin', 'hr' => 'HR'] as $role => $label) {
            if ($user->role === $role && $data['role'] !== $role && User::where('role', $role)->count() <= 1) {
                throw new BusinessRuleException(
                    "This is the only {$label} account. Give another person the {$label} role first.",
                    "last_{$role}",
                    ['role' => ["This is the only {$label} account. Give another person the {$label} role first."]],
                );
            }
        }

        // Don't leave a department with staff but no Head to approve their leave.
        $stopsBeingHead = $data['rank'] !== 'head' || (int) $data['department_id'] !== $employee->department_id;

        if ($stopsBeingHead && $employee->isLastHeadWithStaff()) {
            $message = 'This is the only Head of ' . $employee->department->name . '. Make someone else in that department Head first.';
            throw new BusinessRuleException($message, 'last_head', ['rank' => [$message]]);
        }

        DB::transaction(function () use ($employee, $user, $data) {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
            ]);

            $employee->update([
                'department_id' => $data['department_id'],
                'rank' => $data['rank'],
                'date_of_birth' => $data['date_of_birth'],
                'date_hired' => $data['date_hired'],
            ]);

            // Only touch skills when the form sent them (older clients may not).
            if (array_key_exists('skills', $data)) {
                $this->syncSkills($employee, $data['skills'] ?? []);
            }
        });

        return $employee->refresh();
    }

    public function delete(Employee $employee): void
    {
        $user = $employee->user;

        foreach (['admin' => 'Admin', 'hr' => 'HR'] as $role => $label) {
            if ($user->role === $role && User::where('role', $role)->count() <= 1) {
                throw new BusinessRuleException(
                    "This is the only {$label} account and can't be deleted. Give another person the {$label} role first.",
                    "last_{$role}",
                );
            }
        }

        if ($employee->isLastHeadWithStaff()) {
            throw new BusinessRuleException(
                'This is the only Head of ' . $employee->department->name . '. Make someone else in that department Head first.',
                'last_head',
            );
        }

        // Soft delete: the person and their account are hidden and can't sign in, but can be
        // restored. Requests still waiting for a decision are cancelled; past leave stays on record.
        DB::transaction(function () use ($employee, $user) {
            $employee->leaveRequests()
                ->whereIn('status', ['pending_head', 'pending_hr'])
                ->update(['status' => 'cancelled', 'cancellation_reason' => 'Employee removed from the system']);

            $employee->delete();
            $user->delete();
        });
    }

    // Bring back a deleted employee and their account.
    public function restore(Employee $employee): Employee
    {
        if (! $employee->trashed()) {
            throw new BusinessRuleException('This employee is not deleted.', 'not_deleted');
        }

        if (! $employee->department()->exists()) {
            throw new BusinessRuleException(
                'Their department has been deleted. Restore the department first.',
                'department_deleted',
            );
        }

        DB::transaction(function () use ($employee) {
            $employee->user()->withTrashed()->first()?->restore();
            $employee->restore();
        });

        return $employee->refresh();
    }

    // Replace the employee's skills: $skills = [['id' => 3, 'level' => 'expert'], …]
    public function syncSkills(Employee $employee, array $skills): void
    {
        $employee->skills()->sync(
            collect($skills)->mapWithKeys(fn ($s) => [(int) $s['id'] => ['level' => $s['level'] ?? 'intermediate']])->all()
        );
    }

    private function ensureHeadIsEmployeeRole(string $role, string $rank): void
    {
        if ($role !== 'employee' && $rank === 'head') {
            throw new BusinessRuleException(
                'Only employees can be Head of Department.',
                'head_must_be_employee',
                ['rank' => ['Only employees can be Head of Department.']],
            );
        }
    }
}
