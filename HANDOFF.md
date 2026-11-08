# Handoff — HR Management System

_Last updated: 2026-11-08_

## Where we are

- **Repo:** `Leyeft/Re_HR_Management` (our own repo — **do not work in the GitHub Classroom repo**).
- **Branch:** `react-frontend` — all work happens here. `main` still has the old Blade version.
- **App:** Laravel 12 + React (via Inertia) + MySQL. The Laravel app is in `hr-system/hr-system/`.
- **Status:** React conversion complete. Most bugs below are now fixed, plus new features added
  (see **Progress — 2026-11-07**). App builds and runs locally.

## Progress — 2026-11-07

**Bugs fixed** (numbers refer to the Findings tables below):
- #1 `role` now in `User::$fillable` — roles save correctly. Existing `admin@test.com`/`hr@test.com` set to admin/hr.
- #2 model typo fixed (`reject_reason` → `rejection_reason`) — Head/HR rejection reasons now save.
- #3 employee update rank limited to `employee`/`head`.
- #5 public registration **removed** (closed system — Admin/HR create accounts). Leave pages are now
  null-safe for accounts with no employee profile (show a notice / redirect, no 500).
- #8 + #11 employee & department management restricted to **Admin + HR** via `EnsureAdminOrHr`
  middleware (`app/Http/Middleware/EnsureAdminOrHr.php`); nav links hidden from others.
- #9 Head-only check on the approvals page restored (null-safe).
- #12 logo replaced (Crewly). #13 `/` redirects (login or dashboard); Welcome register link removed.

**New features**
- **Login page** redesigned (`Pages/Auth/Login.jsx`) — split-screen Crewly design, wired to real auth.
- **Change Password** moved to its own page (`Pages/Profile/Password.jsx`, route `profile.password`),
  reached by a "Reset Password" button on the profile; sits behind `auth`.
- **Two-step leave approval:** Employee → **Head** (`pending_head`) → **HR** (`pending_hr`) → `approved`.
  Reject at either stage ends it. Admin can also give the final sign-off. Audit columns added
  (`head_approved_by/at`, `hr_approved_by/at`) — migration `2026_11_07_000000_add_hr_stage_to_leave_requests`.
- **Dashboard** is role-scoped: Admin/HR see org-wide stats; everyone else sees only their own leave summary.
- **Delete employee** uses a type-`DELETE` confirmation modal. Deleting the **last Admin or last HR**
  is blocked (themed in-modal message + server backstop) so the realm always keeps a ruler and a healer.
- **Theme** applied app-wide (navy/teal) — see Branding & theme below.

**New features (later on 2026-11-07, committed `648c023` onward):**
- **Leave entitlement by years of service** (from a new **Date Hired** field): <1y=3, 1–7y=10, 8–15y=15,
  16+y=20. Enforced on annual requests; balance shown on dashboard, leave pages, request form,
  employee list, create/edit. (migration `...000002_add_date_hired_to_employees`)
- **Employee details popup** (click a name) on Employees, Approvals, History, All Leave — shows profile +
  this year's leave entries. **"View more"** (Admin/HR) opens a full **profile page** `employees/{id}`
  with full leave history and approver names.
- **Employee ID** = `department_id * 100000 + sequence` (e.g. 100001, 200001), auto-assigned on create,
  permanent. (migration `...000004_add_employee_code_to_employees`)
- **Leave types**: annual (counts, Head→HR), **sick** (own 30-day/yr cap, past dates allowed,
  auto-recorded no approval), **professional** (no limit, Head→HR).
  (migration `...000003_add_type_to_leave_requests`)
- **A Head's own leave skips to HR** (can't self-approve).
- **Overlap check**: can't submit a request overlapping a live one.
- **Cancel a request** (owner only) with a required reason → status `cancelled`.
  (migration `...000005_add_cancel_to_leave_requests`)
- **Department delete blocked** when it still has employees (castle message + server backstop);
  Departments list shows an employee count.

## Progress — 2026-11-08

- **Working days:** leave counts Mon–Fri only and skips **public holidays** (e.g. Mon–Wed = 3 days;
  Mon–Wed with a Tuesday holiday = 2). `LeaveRequest::workingDaysBetween`, mirrored in
  `Utils/leave.js` `workingDays` (holidays shared to the browser as the `holidays` Inertia prop).
  Requests with no working days are refused.
- **Public holidays:** HR/Admin add/remove them on the Leave Settings page (`holidays` table,
  `App\Models\Holiday`). "Same date every year" makes it recurring (e.g. Christmas); otherwise it's
  a one-off date (for holidays that move each year). Seeder adds New Year's Day + Christmas.
- **Leave rollover (HR decides):** new **Leave Settings** page (`/settings/leave`, Admin/HR only) with an
  on/off switch and a max-days cap (default off, max 5). When on, last year's unused annual days
  (entitlement − approved annual) are added to this year, capped. Stored in a new `settings` table
  (`App\Models\Setting`). Balance now returns `carried_over` and `total`.
- **Last Head protection:** a department's only Head can't be demoted, moved or deleted while the
  department still has other staff (form error / themed delete-dialog blocker + server check).
  Fallback: if a department has **no** Head (e.g. brand new), leave goes straight to HR.
- **#4 fixed:** Edit Employee now has a System Role field. Can't remove the last Admin/HR via a role
  change; HR/Admin still can't also be Head.
- **#7 fixed:** renaming a department to a name that already exists shows "Another department
  already has this name" instead of a 500.
- **#14 fixed:** app name is **Crewly** (`.env`, `.env.example`, fallbacks).
- **#15 fixed:** tests now pass — **98 passed**. The 2026-11-07 migrations used MySQL-only
  `ALTER … MODIFY`, which broke every test on SQLite; switched to Schema `->change()` (same result
  on MySQL). Outdated tests rewritten; new suites: `LeaveFlowTest`, `LeaveRolloverTest`,
  `EmployeeManagementTest`, `DepartmentTest`.
- **#16 fixed:** `DemoSeeder` (called by `DatabaseSeeder`): 5 departments, Admin, HR, a Head per
  department, staff, leave in every status. All passwords `password`. Re-runnable (skips existing
  emails). Fresh demo DB: `php artisan migrate:fresh --seed`. Empty `DevSeeder` removed.

- **Filters + exports:** All Leave now has filters (employee, department, status, type, date range —
  a range keeps any leave that overlaps it). CSV + PDF export on **All Leave** and **Employees**, using
  the page's current filters (shared queries: `LeaveRequest::scopeFilter`, `Employee::listQuery`).
  **Download PDF** on an employee's profile = their full leave record. `ReportController`, PDF views in
  `resources/views/reports/` (dompdf: `barryvdh/laravel-dompdf`). CSV has a UTF-8 BOM for Excel and
  neutralises formula text (CSV injection). Admin/HR only. Tests: `ReportTest` (13).

**Still open / to decide (next up):**
- App timezone is `UTC` (`config/app.php`), so report "Generated" times and approval times show UTC.
- Year-boundary leave counts against the start year only.
- Employee ID is 7 digits once a department id reaches 10 (still unique) — left as-is unless we
  decide to zero-pad to fixed width.

**Run `php artisan migrate`** on any other machine to apply the 2026-11-07 migrations
(HR stage, department description, date hired, leave type, employee code, cancel) and the
2026-11-08 `settings` and `holidays` tables.

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
cd C:\laragon\www\HR_Management\final-project-Leyeft-1\hr-system\hr-system
php artisan serve        # http://127.0.0.1:8000
npm run dev              # second terminal, only while editing React files
```
`/` redirects to the dashboard if signed in, otherwise the login page.

**Test accounts** (local database): `admin@test.com` (admin) and `hr@test.com` (hr) — password `password`.
Department heads: `james@test.com` (Finance), `itsupport@test.com`, `housekeep@test.com`, `maintain@test.com`.
Employees e.g. `bobby@test.com`. Most passwords are `password`; `itsupport`/`jackie` are `12345678`.

**Tests:** `php artisan test` → 111 passed — 32 unit tests (`tests/Unit`: allowance tiers, working days + holidays, settings, employee IDs) and 79 feature tests (SQLite in memory; no build or MySQL needed).

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

## Branding & theme (added 2026-11-07)

- **Logo:** "Crewly" SVGs live in `hr-system/hr-system/public/logo/`
  (`crewly-logo.svg` light, `crewly-logo-on-dark.svg` for dark backgrounds, plus icon + favicon).
  Rendered via `resources/js/Components/ApplicationLogo.jsx` — pass `dark` for the on-dark version.
  Used in the top nav (dark) and the login/register pages (light).
- **Color palette** — defined once as CSS variables in `resources/css/app.css` (`:root`) and
  exposed to Tailwind in `tailwind.config.js` as `primary`, `accent`, `success`, `warning`,
  `danger`, `info` (each with `-bg`/`hover`/`text`/`tint` where relevant), plus `bg`, `surface`,
  `line`, `content`, `muted`. Use these classes (e.g. `bg-primary`, `text-accent`) instead of
  raw Tailwind colors so the whole app stays consistent. To re-theme, edit the hex values in
  `app.css` only — nothing else needs to change.

```css
:root {
  --color-primary: #12305A;  --color-primary-hover: #0C2342;
  --color-accent: #2EC4B6;   --color-accent-text: #0B7A72;  --color-accent-tint: #E0F6F3;

  --color-bg: #F4F7FA;       --color-surface: #FFFFFF;
  --color-border: #DDE4EC;   --color-text: #1A2B3C;         --color-text-muted: #4A5D73;

  --color-success: #187A46;  --color-success-bg: #E3F4EA;
  --color-warning: #9A5B00;  --color-warning-bg: #FDF1DC;
  --color-danger:  #C93A3A;  --color-danger-bg:  #FBE7E7;
  --color-info:    #2F6FCC;  --color-info-bg:    #E6EFFB;
}
```

- **Applied to:** page background, navy top nav (light text + teal active underline), `PrimaryButton`
  (navy), `SecondaryButton`, `DangerButton`, nav links, `LeaveStatus` badges, `FlashMessage`,
  `PageHeader`. Run `npm run build` after changing colors.
