# Handoff — HR Management System

_Last updated: 2026-11-03_

## Where we are

- **Repo:** `Leyeft/Re_HR_Management` (our own repo — **do not work in the GitHub Classroom repo**).
- **Branch:** `react-frontend` — all work happens here. `main` still has the old Blade version.
- **App:** Laravel 12 + React (via Inertia) + MySQL. The Laravel app is in `hr-system/hr-system/`.
- **Status:** the React conversion (commit `968c3b2`, 2026-11-02) is complete. The app installs, builds and
  runs locally. No fixes from the list below have been made yet.

## Local setup (done on this PC on 2026-11-03)

| Tool | Version | Notes |
|---|---|---|
| Git | 2.56 | |
| PHP | 8.4 (NTS) | `php.ini` created; enabled curl, fileinfo, intl, mbstring, openssl, pdo_mysql, pdo_sqlite, sqlite3, zip |
| Composer | 2.11 | Installed next to `php.exe` (`composer.bat` + `composer.phar`) |
| Node.js / npm | 26.13 / 11.20 | |
| MySQL | 8.4 | Windows service `MySQL84`, auto-starts, port 3306, user `root` with **no password** (local dev only) |

- Database `hr_system` created, all migrations run.
- `.env` created from `.env.example` (MySQL settings, app key generated). It is git-ignored.
- `composer install`, `npm ci`, `npm run build` all done.

**Run the app:**
```
cd E:\Toto\Project\hr-system\hr-system
php artisan serve
```
Open http://127.0.0.1:8000 (`/` redirects to the login page).

**Local admin account** (exists only in the local database, not in the code):
`admin@example.com` / `password` — role `admin`, department "Administration".
Its role had to be set directly in MySQL because of bug #1 below.

**Tests:** `php artisan test` → 24 passed, 2 failed. Both failures (`ExampleTest`, `DepartmentTest`) are
outdated tests expecting `/` to return 200; the app redirects to login, so they get 302.

## Roles in the app

- `users.role`: `employee` (default), `hr`, `admin` — system access.
- `employees.rank`: `employee` or `head` — department hierarchy. (The database also allows `manager`, but the
  app never uses it.)
- Head of Department approves/rejects leave for their department. HR has a read-only view of all leave.
  Admin manages departments and employees.

## Findings

### 🔴 Broken features

| # | Problem | Where | Effect |
|---|---|---|---|
| 1 | `role` not in User `$fillable` | `app/Models/User.php:22` | Every new user is saved as `employee`, even when HR/Admin is chosen |
| 2 | `$fillable` says `reject_reason`, column is `rejection_reason` | `app/Models/LeaveRequest.php:16` | Rejection reason typed by the Head is never saved |
| 3 | Employee update accepts rank `admin`/`hr`; DB enum is `employee`/`head`/`manager` | `app/Http/Controllers/EmployeeController.php:130` | Choosing those ranks → server error (500) |
| 4 | Employee edit can't change `role` | `EmployeeController@update` + `Employees/Edit.jsx` | No way to promote someone to HR/Admin after creation |
| 5 | Public `/register` creates users with no employee record | `Auth/RegisteredUserController.php` | Leave/approval pages crash for those users (`$employee` is null) |
| 6 | `employees.department_id` is `cascadeOnDelete` | `database/migrations/2025_12_15_015632_create_employees_table.php:18` | Deleting a department deletes its employee rows but leaves their user accounts (which then crash) |
| 7 | Department update doesn't validate name uniqueness | `DepartmentController@update` | Duplicate name → database error (500) |

### 🟠 Security / permissions

| # | Problem | Where |
|---|---|---|
| 8 | No Admin-only check on departments and employees — any logged-in user can create/edit/delete | `routes/web.php:95-96`, both controllers |
| 9 | Head-only check commented out on the approvals page | `LeaveApprovalController.php:16-18` |
| 10 | A Head can approve/reject **their own** leave request | `LeaveApprovalController::authorizeAction` |
| 11 | Nav shows Departments/Employees to everyone | `resources/js/Layouts/AuthenticatedLayout.jsx:13-14` |

### 🟡 Branding / cleanup

| # | Change |
|---|---|
| 12 | Replace the default Laravel logo — `resources/js/Components/ApplicationLogo.jsx` |
| 13 | Replace/remove the default Laravel welcome page — `resources/js/Pages/Welcome.jsx` (currently unused) |
| 14 | Set the app name (`APP_NAME` in `.env` / `.env.example`) |
| 15 | Fix the 2 outdated tests; add tests for leave flow and permissions |
| 16 | Add a sample-data seeder (admin, HR, Head, employees, departments). `DevSeeder` is empty; `DatabaseSeeder` only makes a test user |

## Database changes

Most fixes are code-only. Planned database change (as a **new migration**, not by editing old ones):

- **#6:** change `employees.department_id` from `cascadeOnDelete` to `restrictOnDelete`, so a department with
  employees can't be deleted. The app should show a friendly message ("Move or remove the employees first").
- **#3:** code-only fix (limit rank to `employee`/`head`) unless we decide to keep a Manager rank.

## Plan

1. **Fix #1–#11** (backend bugs + permissions), plus the migration for #6.
2. **Branding** (#12–#14), after choosing a logo and name.
3. **Tests + sample-data seeder** (#15–#16).

## Open decisions (answer before starting step 1)

- [ ] **Disable public sign-up (#5)?** Recommended: yes — only Admin creates accounts (normal for HR systems).
- [ ] **Manager rank?** Recommended: no — keep only Employee and Head for now.
- [ ] **Logo:** use our own image, or have a simple one made?
