# How Crewly works: study guide

This guide explains how the code fits together, so you can find your way around, explain it at the defense, and change it with confidence. Paths are relative to `hr-system/hr-system/` unless stated.

**Contents**
1. [The big picture](#1-the-big-picture)
2. [Where things live](#2-where-things-live)
3. [One request from start to finish](#3-one-request-from-start-to-finish)
4. [How each feature works](#4-how-each-feature-works)
5. [If you're asked to change something](#5-if-youre-asked-to-change-something)
6. [Debugging](#6-debugging)
7. [Likely defense questions](#7-likely-defense-questions)

---

## 1. The big picture

Crewly is **one Laravel app** that does two jobs:

1. It serves a **React single-page app (SPA)**. The browser loads one HTML page, `resources/views/spa.blade.php`, once. After that, React draws every screen, and **React Router** switches between them without reloading the page.
2. It provides a **REST API** under `/api/*`. The React app asks it for data and sends it changes, all as JSON.

```
Browser (React SPA)                                   Laravel
┌─────────────────────────────┐                ┌───────────────────────────────────┐
│ Page (e.g. RequestLeave.jsx)│                │ routes/api.php                    │
│   ↓ dispatch(thunk)         │   HTTP + JSON  │   ↓ middleware (auth, role, head) │
│ Redux slice (leaveSlice.js) │ ─────────────► │ FormRequest (validation)          │
│   ↓ Axios (api/client.js)   │                │   ↓ Controller (thin)             │
│                             │ ◄───────────── │ Service (business rules)          │
│ store updates → page redraws│   JSON reply   │   ↓ Model (Eloquent) → MySQL      │
└─────────────────────────────┘                │ Resource (shapes the JSON)        │
                                               └───────────────────────────────────┘
```

**One rule worth remembering:** every layer has a single job.

| Layer | Job | Folder |
| --- | --- | --- |
| Route | Which URL goes to which controller, and who may call it | `routes/api.php` |
| Middleware | "Are you signed in? Do you have the right role?" | `app/Http/Middleware`, `bootstrap/app.php` |
| FormRequest | "Is the input valid?" Field rules and custom error messages | `app/Http/Requests/Api` |
| Controller | Glue: takes the request, calls a service, returns a Resource | `app/Http/Controllers/Api` |
| Service | Business rules: allowance, approvals, safeguards | `app/Services` |
| Model | One database table, its relationships and helpers | `app/Models` |
| Resource | Exactly which fields go into the JSON (never passwords) | `app/Http/Resources` |
| Policy | Fine-grained permission, e.g. "only an Admin can edit an Admin" | `app/Policies` |

---

## 2. Where things live

### Backend (`app/`, `routes/`, `database/`)

| What | File(s) |
| --- | --- |
| All API URLs | `routes/api.php` |
| Report downloads, holiday template, email-verify link, the SPA page | `routes/web.php` |
| Middleware setup, **JSON error format**, trusted proxies | `bootstrap/app.php` |
| Sign-in, sign-out, API tokens, "who am I", passwords | `Controllers/Api/AuthController.php` |
| Leave rules (submit, approve, reject, cancel, notifications) | `Services/LeaveService.php` |
| Staff rules (create, update, delete, restore, last Admin/HR/Head) | `Services/EmployeeService.php` |
| Holiday Excel/CSV import | `Services/HolidayImportService.php` |
| Allowance, rollover, leave balance | `Models/Employee.php` (sections 4 and 6) |
| Working-day counting | `Models/LeaveRequest.php` → `workingDaysBetween()` |
| Activity log (automatic) | `Models/Concerns/LogsActivity.php`, `Models/ActivityLog.php` |
| In-app notifications | `Notifications/LeaveUpdate.php` |
| Admin vs HR rule | `Policies/EmployeePolicy.php` |
| CSV and PDF reports | `Controllers/ReportController.php`, `resources/views/reports/*.blade.php` |
| Database tables | `database/migrations/` (one file per change, oldest first) |
| Demo data | `database/seeders/DemoSeeder.php`, `database/factories/` |

### Frontend (`resources/js/spa/`)

| What | File(s) |
| --- | --- |
| Start-up: creates the store, checks the session, mounts React | `main.jsx` |
| Every page and who may open it | `routes/AppRoutes.jsx`, `routes/guards.jsx` |
| The menu bar, bell, user menu | `layouts/AppLayout.jsx` |
| Talking to the API (cookie, CSRF, errors) | `api/client.js` |
| Global state, one slice per feature | `store/*.js` (listed in `store/index.js`) |
| Pages | `pages/**` |
| Reusable parts (dialogs, pagination, avatar, charts…) | `components/` and `../Components/` (buttons, inputs, modal) |
| Form helper (field errors, toasts) | `hooks/useApiForm.js` |
| Leave helpers shared with the server's rules | `../Utils/leave.js` (allowance tiers, working days) |

---

## 3. One request from start to finish

**Example: Bobby requests 3 days of annual leave.** Follow the numbers through the files.

1. **Page:** `pages/leave/RequestLeave.jsx`. Bobby picks Monday to Wednesday. The page counts working days in the browser with `workingDays()` from `Utils/leave.js`, skipping weekends and holidays (taken from the `holidays` slice), and shows "3 working days".
2. **Submit:** the form calls `form.submit(...)` from `hooks/useApiForm.js`, which dispatches the thunk `submitLeave(values)` from `store/leaveSlice.js`.
3. **Thunk to Axios:** `submitLeave` calls `api.post('/leave-requests', values)`. The Axios instance in `api/client.js` sends the session cookie and the `X-XSRF-TOKEN` header automatically.
4. **Route:** in `routes/api.php`, `POST leave-requests` sits inside `auth:sanctum`. Laravel checks that Bobby is signed in, and returns 401 if he isn't.
5. **Validation:** the controller's parameter is `StoreLeaveRequest`, so Laravel validates first. Wrong input comes back as 422, `{code: "validation_error", errors: {...}}`, before the controller runs.
6. **Controller:** `LeaveRequestController@store` finds Bobby's employee record and calls `LeaveService::submit($employee, $data)`.
7. **Service** (the business rules, in order):
   - Count working days with `LeaveRequest::workingDaysBetween()`. If there are 0, throw `BusinessRuleException('…', 'no_working_days')`.
   - Check for overlaps with live requests, which throws `overlapping_leave`.
   - Check the allowance using `$employee->leaveSummary()`, which throws `not_enough_leave`.
   - Choose the first status with `firstStatus()`: sick leave becomes `approved`; a Head's own leave, or a department with no Head, becomes `pending_hr`; everything else becomes `pending_head`.
   - Save with `LeaveRequest::create(...)`. The **LogsActivity** trait writes "Created leave request #12 …" to the activity log.
   - **Notify** the department's Head through `LeaveUpdate`, stored in the `notifications` table.
8. **Response:** the controller returns `new LeaveRequestResource($leave)` with status 201. The Resource decides which fields appear, including `days`.
9. **Back in the browser:** the thunk resolves, a toast appears ("Leave request sent to your Head of Department."), and the page navigates to My leave.
10. **The Head's side:** James's bell (`components/NotificationBell.jsx`) checks `/api/notifications/unread-count` every 30 seconds and shows "1".

**If something fails,** for example not enough leave: the service throws `BusinessRuleException`. The handler in `bootstrap/app.php` turns it into `422 {code: "not_enough_leave", message: "…"}`. `api/client.js` normalises the error, and `useApiForm` shows the message as a toast.

---

## 4. How each feature works

### Sign-in and sessions
- **SPA:** the app calls `GET /sanctum/csrf-cookie`, which sets the `XSRF-TOKEN` cookie, then `POST /api/auth/login`. Laravel starts a normal session, kept in a **secure, httpOnly cookie** (`crewly-session`), so JavaScript can never read it. From then on, every request carries the cookie and the XSRF header. That's Sanctum's "SPA authentication", turned on by `$middleware->statefulApi()` in `bootstrap/app.php`.
- **On page load:** `main.jsx` dispatches `fetchMe()`, which calls `GET /api/auth/me`. If it returns 401, the user counts as signed out.
- **Session ends:** any 401 makes `api/client.js` dispatch `sessionEnded()`, and the guard sends the user to `/login` with a message.
- **API clients** (Postman): `POST /api/auth/token` returns a Sanctum token, sent as `Authorization: Bearer …`.
- **Brute force:** `LoginRequest::authenticate()` blocks after 5 wrong tries, and the auth routes are throttled to 10 per minute.

### Roles and permissions
- There are **two separate ideas**:
  - `users.role`: `employee`, `hr` or `admin`. This is the *system* role.
  - `employees.rank`: `employee` or `head`. This is the *department* rank.
- **API, coarse level:** route groups in `routes/api.php`:
  - `role:admin,hr` uses `EnsureRole`.
  - `head` uses `EnsureDepartmentHead`.
- **API, fine level:** `EmployeePolicy` says only an Admin can grant the Admin role or edit or remove an Admin. It's used in `StoreEmployeeRequest` and `UpdateEmployeeRequest` (`authorize()`), and with `Gate::authorize` in `EmployeeController`.
- **The service** checks ownership. For example, `LeaveService::ensureHeadCanAct()` requires the Head to be in the same department as the request.
- **Frontend:** `GET /api/auth/me` returns a `can` map (`manage_staff`, `manage_admins`, `sign_off_leave`, `approve_department_leave`, `request_leave`).
  - `AppLayout.jsx` hides menu links the user can't use.
  - `RequireCan` in `routes/guards.jsx` redirects with "You don't have access to that page."
  - **This hiding is only for convenience.** The API checks everything again.

### Leave rules
All rules are in `LeaveService` (see section 3), and the statuses are listed at the top of `Models/LeaveRequest.php`. Approvals:

| Step | Endpoint | Service method | Status after |
| --- | --- | --- | --- |
| Head approves | `POST /api/approvals/{id}/approve` | `headApprove()` | `pending_hr`, and HR is notified |
| Head rejects | `POST /api/approvals/{id}/reject` | `headReject()` | `rejected`, and the employee is notified |
| HR approves | `POST /api/hr/leave-requests/{id}/approve` | `hrApprove()` | `approved`, and the employee is notified |
| HR rejects | `…/reject` | `hrReject()` | `rejected`, and the employee is notified |
| Owner cancels | `POST /api/leave-requests/{id}/cancel` | `cancel()` | `cancelled`, and whoever was due to act is notified |

### Allowance and rollover (`Models/Employee.php`, section 4)
- `yearsOfService()` counts whole years since `date_hired`.
- `leaveEntitlement()` gives under 1 year → 3 days, 1–7 → 10, 8–15 → 15, 16+ → 20. **The same tiers are in `Utils/leave.js` → `tierFromYears()`**, for the hint on the employee form.
- `carriedOverDays($year)` applies only if HR switched rollover on (`Setting::rolloverEnabled()`). It takes last year's allowance minus last year's approved annual days, capped at `Setting::rolloverMaxDays()`.
- `leaveSummary($year)` puts it all together: allowance, carried over, used, pending and left, for each type. The dashboard, forms, reports and the "not enough leave" check all use it.

### Working days and holidays
- `LeaveRequest::workingDaysBetween($start, $end)` counts Monday to Friday, skipping dates in `Holiday::lookup()`.
- `Holiday::lookup()` loads all holiday dates **once per request** and keeps them in the container, because the count runs many times per page.
- **Import** (`HolidayImportService`): it reads .xlsx or .csv with **OpenSpout**, finds the `date` and `name` columns, and parses dates (`2026-12-25`, `25/12/2026`, or Excel date cells). It compares each row with what's saved: **new** (add), **replace** (same date, new name), **unchanged**, **duplicate**, or **error**. `dry_run=1` returns this preview without saving.
- **Clear all:** `DELETE /api/holidays` deletes in one query, writes one activity-log line, and calls `Holiday::forgetCache()`.

### Employees and departments
- **Employee ID:** in `Employee::booted()`, a `creating` event sets `employee_code = department id × 100000 + next number`. It counts deleted employees too, so numbers are never reused.
- **Safeguards** (`EmployeeService`): HR or Admin can't also be a Head; the last Admin and the last HR can't be demoted or deleted; a department's only Head can't leave while staff remain (`Employee::isLastHeadWithStaff()`).
- **Soft delete:** the models use `SoftDeletes`, which sets a `deleted_at` date instead of removing the row. Normal queries hide deleted rows, `?trashed=only` lists them, and `/restore` endpoints bring them back. Deleting a person also cancels their pending leave and blocks their sign-in.
- **Department hierarchy:** `departments.parent_id` points to another department (a *self-referencing* foreign key). `UpdateDepartmentRequest::after()` blocks loops using `Department::descendantIds()`. Deleting a department moves its children up a level.
- **Skills:** a many-to-many relationship through the **pivot table** `employee_skill`, which also stores a `level`. `EmployeeService::syncSkills()` replaces the list with `sync()`.

### Notifications
- `Notifications/LeaveUpdate.php` uses the `database` channel, so each notification is a row in the `notifications` table, holding a title, message and the SPA link to open.
- `LeaveService::notify()` and `notifyOwner()` send them, never to the person who did the action.
- The SPA: `notificationsSlice` and `NotificationBell` check the unread count every 30 seconds, only while the tab is visible. Email could be added later by returning `['database', 'mail']` from `via()`.

### Activity log
- `Models/Concerns/LogsActivity.php` is a **trait**: adding `use LogsActivity` to a model makes Laravel's model events (`created`, `updated`, `deleted`, `restored`) write to `activity_logs` automatically, with the old and new values of changed fields.
- Passwords are listed in `$logHidden`, so they show as "(hidden)".
- Sign-ins are logged by an event listener in `AppServiceProvider`. Skill changes are logged by hand in `EmployeeService::syncSkills()`, because changes to the link table don't fire model events.

### Reports (CSV and PDF)
- `ReportController` reuses **the same query as the page**: `LeaveRequest::scopeFilter()` and `Employee::listQuery()`. The file therefore always matches the screen.
- **PDF:** dompdf renders the Blade templates in `resources/views/reports/`.
- **CSV:** streamed with a BOM so Excel shows names correctly, and `safeCell()` stops "CSV injection" (text like `=HYPERLINK(...)` running as a formula).
- These are normal links on **web routes**, so the browser sends the session cookie automatically.

### Profile photo
- `UploadAvatarRequest` accepts real JPG, PNG or WebP images only, up to 2 MB and 64–4000 px. **SVG is refused** because it can contain scripts.
- `ProfileController::uploadAvatar()` stores the photo under a random name on the disk named by `AVATAR_DISK`, and deletes the old one.

### Errors (one shape everywhere)
- **Server:** `bootstrap/app.php` → `withExceptions()` turns every exception on `/api/*` into `{code, message, errors}`. Business rules throw `App\Exceptions\BusinessRuleException` with their own `code`.
- **Client:** `api/client.js` → `normalizeError()` gives the same shape even for network errors. It retries once on 419, an expired CSRF token. `useApiForm` puts field errors under the inputs and shows any other error as a toast.

### Frontend state (Redux Toolkit)
- `store/index.js` combines one slice per feature: auth, toasts, dashboard, departments, employees, leave, holidays, settings, skills, notifications and activity.
- A slice has an initial state, `extraReducers` that react to thunks (`pending`, `fulfilled`, `rejected`), and thunks made with `apiThunk(type, request)` from `store/apiThunk.js`.
- **Pages read data with `useSelector`** and change it with `dispatch(thunk)`. Only typing in a form is kept locally in the component.
- Every slice resets on `logout`/`sessionEnded`, so the next person never sees the previous user's data.

### Tests
- **Unit tests** (`tests/Unit`) check single rules: tiers, working days, settings and IDs.
- **Feature tests** (`tests/Feature/Api`) send real API requests as a given role.
- Each test starts from an empty SQLite database in memory. `tests/TestCase.php` has the helpers `makeEmployee()` and `nextWeekday()`.
- Run them with `php artisan test`. The full list is in [`TESTING.md`](../TESTING.md).

---

## 5. If you're asked to change something

These are typical "modify it live" tasks, with the exact places to edit. After any change, run `php artisan test`, and run `npm run build` (or keep `npm run dev` running) if you changed React code.

| Task | Where |
| --- | --- |
| **Change the allowance tiers** (e.g. 1–5 years = 12 days) | `app/Models/Employee.php` → `leaveEntitlement()` and `leaveTierLabel()`; `resources/js/Utils/leave.js` → `tierFromYears()`; update `tests/Unit/LeaveEntitlementTest.php` |
| **Change the sick-leave cap** | `Employee::SICK_DAYS_PER_YEAR` |
| **Change the error text for not enough leave** | `app/Services/LeaveService.php` → `submit()` |
| **Change a validation message** | The FormRequest's `messages()`, e.g. `app/Http/Requests/Api/StoreLeaveRequest.php` |
| **Add a field to employees** (e.g. phone) | 1. New migration: `php artisan make:migration add_phone_to_employees_table`. 2. `Employee::$fillable`. 3. Rules in `StoreEmployeeRequest` and `UpdateEmployeeRequest`. 4. `EmployeeService` create/update. 5. `EmployeeResource`. 6. The input in `pages/employees/EmployeeForm.jsx`, shown in `EmployeeProfile.jsx` |
| **Add a leave type** (e.g. "maternity") | A migration to change the `type` enum; `StoreLeaveRequest` (`in:` rule); `LeaveService` (allowance rule, if any); `Utils/leave.js` → `LEAVE_TYPE_LABELS`; `Components/LeaveTypeBadge.jsx`; the `<option>` in `RequestLeave.jsx` |
| **Add a new page with its own data** | Route in `routes/api.php` → controller (+ FormRequest + Resource) → thunk in a slice → page in `pages/` → `<Route>` in `routes/AppRoutes.jsx` → menu link in `layouts/AppLayout.jsx` (`NAV_LINKS`) |
| **Let another role open a page** | The middleware in `routes/api.php`, the `can` map in `AuthController::profile()`, `RequireCan` in `AppRoutes.jsx`, and the `can` value in `NAV_LINKS` |
| **Change the photo size limit** | `UploadAvatarRequest` (`max`) and `MAX_BYTES` in `pages/Profile.jsx` |
| **Change the brand colours** | The CSS variables in `resources/css/app.css` (`:root`) |

---

## 6. Debugging

| Symptom | Where to look |
| --- | --- |
| Error 500 | `storage/logs/laravel.log` (locally), or the Railway logs. Set `APP_DEBUG=true` **locally only** to see details in the response |
| A page shows wrong data | Browser DevTools → **Network** tab: click the `/api/...` request and check its Response |
| Store contents | The Redux DevTools browser extension shows every action and the state after it |
| Which URL does what | `php artisan route:list --path=api` |
| Try code against the database | `php artisan tinker`, e.g. `App\Models\Employee::first()->leaveSummary()` |
| A test fails | `php artisan test --filter=NameOfTheTest` |

---

## 7. Likely defense questions

**Why is this a REST API + SPA, and not normal Laravel pages?**
The brief requires it. The backend only returns JSON, and React draws the pages. Any other client, like Postman or a mobile app, can use the same API.

**How does authentication work? Where's the token?**
The SPA uses Sanctum's cookie session: no token in JavaScript, so nothing can be stolen by injected script. CSRF is protected by the XSRF token. API clients get a Sanctum token from `/api/auth/token`.

**How do you stop someone calling an API they shouldn't?**
Middleware on the routes (`auth:sanctum`, `role:admin,hr`, `head`), a policy for Admin accounts, and ownership checks in the services. Hiding buttons in React is only for convenience.

**What's the difference between role and rank?**
Role (`users.role`) is system access: employee, HR or admin. Rank (`employees.rank`) is the department position: employee or head. A Head is an employee with extra approval duties.

**Where is the business logic, and why there?**
In `app/Services`. That keeps controllers thin and keeps each rule in one place that is easy to test.

**What's a FormRequest? A Resource?**
A FormRequest validates input and has custom messages, before the controller runs. A Resource controls the JSON output, so private fields like passwords never leak.

**How are working days calculated?**
`LeaveRequest::workingDaysBetween()` loops from the start to the end date, skipping Saturdays, Sundays and dates in the holidays table. The same logic runs in the browser (`Utils/leave.js`), so the form can show the count before submitting.

**What does Redux do here?**
It holds the app's data in one store. Each feature has a slice. Pages dispatch thunks, which call the API through Axios and put the result in the store. Pages read the data with `useSelector` and redraw automatically when it changes.

**Show me the self-referencing relationship and the pivot table.**
`departments.parent_id` points to `departments.id` (`Department::parent()` / `children()`). `employee_skill` links employees and skills, with an extra `level` column (`belongsToMany(...)->withPivot('level')`).

**What happens when you delete an employee?**
A soft delete: the row gets a `deleted_at` date, the user can't sign in, pending leave is cancelled, and history is kept. It can be restored. The last Admin, last HR, or a department's only Head can't be deleted.

**How does the activity log work without code in every controller?**
The `LogsActivity` trait listens to Eloquent model events and writes a row for each create, update, delete or restore, with the old and new values.

**How do you prevent SQL injection, XSS, CSRF and upload attacks?**
Eloquent uses parameter binding. React escapes output. Sanctum checks the XSRF token. Uploads are validated by their real content and type, with SVG refused and random file names. CSV exports neutralise formulas.

**How is it tested?**
169 automated tests: unit tests for rules, and API feature tests for every endpoint and permission. They run on SQLite in memory in a few seconds.

**How is it deployed?**
On Railway. Railpack detects Laravel, installs Composer and npm packages, builds React, runs migrations on start, and serves `public/` with FrankenPHP. Settings are environment variables, and MySQL is a separate Railway service. See [`DEPLOYMENT.md`](../DEPLOYMENT.md).
