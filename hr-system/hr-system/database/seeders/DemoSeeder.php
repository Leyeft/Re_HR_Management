<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Sample data for demos: departments, an Admin, an HR officer, a Head for each
 * department, regular employees and leave requests in every status.
 *
 * Every account's password is "password".
 * Safe to re-run: existing accounts (matched by email) are left untouched.
 *
 *   php artisan migrate:fresh --seed     (wipes the database first)
 *   php artisan db:seed                  (adds whatever is missing)
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Factory-made extra staff are only added on a fresh install, so re-running is safe.
        $freshInstall = ! User::withTrashed()->where('email', 'admin@test.com')->exists();

        $departments = collect([
            'Human Resources' => 'Hiring, records and leave sign-off.',
            'Finance' => 'Payroll, budgets and accounts.',
            'IT Support' => 'Computers, network and help desk.',
            'Housekeeping' => 'Rooms and common areas.',
            'Maintenance' => 'Building repairs and upkeep.',
        ])->map(fn ($description, $name) => Department::firstOrCreate(
            ['name' => $name],
            ['description' => $description],
        ));

        // Hierarchy (self-referencing parent_id): Housekeeping and Maintenance sit under Operations.
        $operations = Department::firstOrCreate(['name' => 'Operations'], ['description' => 'Keeps the building running.']);
        foreach (['Housekeeping', 'Maintenance'] as $child) {
            $departments[$child]->parent_id ??= $operations->id;
            $departments[$child]->save();
        }

        // Skills (many-to-many through employee_skill).
        $skills = collect(['Excel', 'First aid', 'Customer service', 'Networking', 'Plumbing',
            'Electrical', 'Accounting', 'Payroll', 'Leadership', 'English'])
            ->mapWithKeys(fn ($name) => [$name => Skill::firstOrCreate(['name' => $name])]);

        // Common yearly holidays — HR adds the rest on the Leave Settings page.
        foreach (['01-01' => "New Year's Day", '12-25' => 'Christmas Day'] as $md => $name) {
            Holiday::firstOrCreate(
                ['date' => now()->year . '-' . $md],
                ['name' => $name, 'recurring' => true],
            );
        }

        // [name, email, system role, department, rank, years ago hired]
        $people = [
            ['Alice Admin', 'admin@test.com', 'admin', 'Human Resources', 'employee', 12],
            ['Hannah Reyes', 'hr@test.com', 'hr', 'Human Resources', 'employee', 6],

            ['James Carter', 'james@test.com', 'employee', 'Finance', 'head', 9],
            ['Ivan Torres', 'itsupport@test.com', 'employee', 'IT Support', 'head', 4],
            ['Helen Cruz', 'housekeep@test.com', 'employee', 'Housekeeping', 'head', 17],
            ['Mark Santos', 'maintain@test.com', 'employee', 'Maintenance', 'head', 3],

            ['Bobby Lim', 'bobby@test.com', 'employee', 'Finance', 'employee', 2],
            ['Fiona Garcia', 'fiona@test.com', 'employee', 'Finance', 'employee', 0],
            ['Jackie Tan', 'jackie@test.com', 'employee', 'IT Support', 'employee', 8],
            ['Kevin Ramos', 'kevin@test.com', 'employee', 'IT Support', 'employee', 1],
            ['Lara Mendoza', 'lara@test.com', 'employee', 'Housekeeping', 'employee', 5],
            ['Noel Bautista', 'noel@test.com', 'employee', 'Maintenance', 'employee', 20],
        ];

        $created = [];
        // "password" locally; set DEMO_PASSWORD on a public server (config/app.php → demo_password).
        $passwordHash = Hash::make(config('app.demo_password'));

        foreach ($people as [$name, $email, $role, $deptName, $rank, $yearsAgo]) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => $passwordHash,
                'role' => $role,
            ]);

            if ($user->employee) {
                continue; // already set up — don't touch it
            }

            $dept = $departments[$deptName];

            $created[$email] = Employee::create([
                'user_id' => $user->id,
                'department_id' => $dept->id,
                'employee_code' => Employee::generateCode($dept->id),
                'rank' => $rank,
                'date_of_birth' => now()->subYears(25 + $yearsAgo)->subDays(40)->toDateString(),
                'date_hired' => now()->subYears($yearsAgo)->subMonths(2)->toDateString(),
            ]);
        }

        // Skills with a level for the demo staff: [email => [skill => level]]
        $skillMap = [
            'admin@test.com' => ['Leadership' => 'expert', 'Excel' => 'intermediate'],
            'hr@test.com' => ['Payroll' => 'expert', 'English' => 'expert', 'Excel' => 'intermediate'],
            'james@test.com' => ['Accounting' => 'expert', 'Excel' => 'expert', 'Leadership' => 'intermediate'],
            'itsupport@test.com' => ['Networking' => 'expert', 'Leadership' => 'beginner'],
            'housekeep@test.com' => ['Customer service' => 'expert', 'First aid' => 'intermediate'],
            'maintain@test.com' => ['Electrical' => 'expert', 'Plumbing' => 'intermediate'],
            'bobby@test.com' => ['Accounting' => 'intermediate', 'Excel' => 'intermediate'],
            'jackie@test.com' => ['Networking' => 'intermediate'],
            'noel@test.com' => ['Plumbing' => 'expert', 'First aid' => 'beginner'],
        ];
        foreach ($skillMap as $email => $list) {
            $employee = $created[$email] ?? null;
            $employee?->skills()->syncWithoutDetaching(
                collect($list)->mapWithKeys(fn ($level, $skill) => [$skills[$skill]->id => ['level' => $level]])->all()
            );
        }

        // A few more people per department, made by the EmployeeFactory (fresh install only).
        if ($freshInstall) {
            foreach (['Finance', 'IT Support', 'Housekeeping', 'Maintenance'] as $deptName) {
                Employee::factory()
                    ->count(2)
                    ->for($departments[$deptName])
                    ->state(fn () => ['user_id' => User::factory()->state(['role' => 'employee', 'password' => $passwordHash])])
                    ->create()
                    ->each(fn (Employee $e) => $e->skills()->attach(
                        $skills->random(2)->pluck('id')->mapWithKeys(fn ($id) => [$id => ['level' => fake()->randomElement(Skill::LEVELS)]])->all()
                    ));
            }
        }

        $this->seedLeave($created);
    }

    // Leave in every status, only for employees created in this run.
    private function seedLeave(array $employees): void
    {
        $hr = User::where('email', 'hr@test.com')->first();
        $head = fn (Employee $e) => Employee::where('department_id', $e->department_id)
            ->where('rank', 'head')->first()?->user;

        // [email, type, starts in N days (negative = past), working days, status, reason]
        $leave = [
            ['bobby@test.com', 'annual', 14, 3, 'pending_head', 'Family trip'],
            ['kevin@test.com', 'annual', 21, 2, 'pending_head', 'Moving house'],
            ['jackie@test.com', 'professional', 10, 2, 'pending_hr', 'Network security seminar'],
            ['james@test.com', 'annual', 30, 5, 'pending_hr', 'Vacation'], // a Head's own leave goes to HR
            ['lara@test.com', 'annual', -30, 2, 'approved', 'Personal errands'],
            ['noel@test.com', 'annual', -60, 5, 'approved', 'Holiday'],
            ['bobby@test.com', 'sick', -10, 1, 'approved', 'Flu'],
            ['fiona@test.com', 'annual', -20, 4, 'rejected', 'Beach trip'],
            ['kevin@test.com', 'annual', 40, 1, 'cancelled', 'Appointment'],
            // Last year — shows how rollover works when HR switches it on.
            ['jackie@test.com', 'annual', -400, 4, 'approved', 'Year-end break'],
        ];

        foreach ($leave as [$email, $type, $offset, $days, $status, $reason]) {
            $employee = $employees[$email] ?? null;
            if (! $employee) {
                continue;
            }

            $start = $this->weekday(now()->addDays($offset));
            $end = $this->addWorkingDays($start, $days - 1);
            $headApproved = in_array($status, ['pending_hr', 'approved'], true)
                && $type !== 'sick' && $employee->rank !== 'head';
            $hrDone = in_array($status, ['approved', 'rejected'], true) && $type !== 'sick';

            // Requested ~2 weeks before it starts; sign-offs in between — never in the future.
            $requestedOn = $start->copy()->subDays(14)->min(now()->subDays(2));
            $signOff = fn (int $days) => $requestedOn->copy()->addDays($days)->min(now()->subDay());

            $leaveRequest = LeaveRequest::create([
                'employee_id' => $employee->id,
                'department_id' => $employee->department_id,
                'type' => $type,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'reason' => $reason,
                'status' => $status,
                'rejection_reason' => $status === 'rejected' ? 'Too many people off that week' : null,
                'cancellation_reason' => $status === 'cancelled' ? 'No longer needed' : null,
                'head_approved_by' => $headApproved ? $head($employee)?->id : null,
                'head_approved_at' => $headApproved ? $signOff(1) : null,
                'hr_approved_by' => $hrDone ? $hr?->id : null,
                'hr_approved_at' => $hrDone ? $signOff(2) : null,
            ]);

            // created_at isn't mass-assignable; set it directly so "Requested on" is realistic.
            $leaveRequest->forceFill(['created_at' => $requestedOn, 'updated_at' => $requestedOn])->save();
        }
    }

    // Move a date forward to Monday if it lands on a weekend.
    private function weekday(Carbon $date): Carbon
    {
        $date = $date->copy()->startOfDay();
        while ($date->isWeekend()) {
            $date->addDay();
        }

        return $date;
    }

    private function addWorkingDays(Carbon $date, int $days): Carbon
    {
        $date = $date->copy();
        while ($days > 0) {
            $date->addDay();
            if (! $date->isWeekend()) {
                $days--;
            }
        }

        return $date;
    }
}
