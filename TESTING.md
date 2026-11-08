# Testing — Crewly HR Management System

_Last updated: 2026-11-09_

Crewly is tested at four levels: unit, integration, system and security. All **164 automated tests pass**: 32 unit tests and 132 integration (API) tests. System tests are run by hand in the browser.

```
cd hr-system\hr-system
php artisan test                      # everything (~5 seconds)
php artisan test --testsuite=Unit     # unit tests only
php artisan test --filter=LeaveRulesApiTest   # one file
```

---

## 4.4.3 Testing Plan

### Scope

Testing covers the features the committee will assess:

- login and accounts
- employee and department management
- leave requests, with two-step approval (Head, then HR)
- leave allowances, rollover and public holidays
- department hierarchy, skills, soft delete and restore
- in-app notifications, activity log, profile photos
- CSV and PDF reports
- role-based access control (Employee, Head, HR, Admin)

Load and stress testing is out of scope, because the system serves one company's staff (tens of users, not thousands).

### Test levels

| Level | What is tested | How | Count |
| --- | --- | --- | --- |
| Unit | Single business rules (allowance tiers, working days, settings, employee IDs) | PHPUnit, automated | 32 |
| Integration | A full HTTP request to the REST API through routes, middleware, FormRequests, controllers, services and the database | Laravel feature tests, automated | 132 |
| System | Complete user tasks in the real app, from login to result | Manual, in the browser | 21 cases |
| Security | Who can see and do what; protection of key accounts; safe sign-in, uploads and exports | Part of the integration tests, plus manual checks | 31 |

### Environment

| Item | Automated tests | System tests |
| --- | --- | --- |
| Server | PHP 8.4, Laravel 12.42 | Same, via `php artisan serve` |
| Database | SQLite in memory, rebuilt for every test | MySQL 8.4, loaded with the demo seeder |
| Frontend | Not needed | React SPA production build (`npm run build`), Chrome |
| Command | `php artisan test` | `php artisan migrate:fresh --seed` |

The automated suite runs in about 5 seconds, so it is re-run after every change. A change is only committed when every test passes.

### Schedule

| Date | Activity | Status |
| --- | --- | --- |
| 2026-11-03 | Code review of the converted React version; 16 issues logged (B01–B16) | Done |
| 2026-11-07 | Fixes for B01–B03, B05, B06 and B08–B13 | Done |
| 2026-11-08 | Fixes for B04, B07 and B14–B23; unit, integration and report tests written; 111 of 111 pass | Done |
| 2026-11-08 | Rebuild as a REST API + React SPA; API tests added for every endpoint; old page tests moved to the API; B24–B26 found and fixed; 153 of 153 pass | Done |
| To schedule | System test run on MySQL with the demo data (section 4.4.4.3) | Planned |
| To schedule | Acceptance test with stakeholders and the evaluation committee | Planned |

---

## 4.4.4.1 Unit Tests

All 32 unit tests pass. Each one checks a single business rule directly, without going through any page. They live in `hr-system/hr-system/tests/Unit/`.

| Test file | Rule under test | Example cases | Tests | Result |
| --- | --- | --- | --- | --- |
| `LeaveEntitlementTest` | Annual allowance by years of service: under 1 year = 3 days, 1–7 = 10, 8–15 = 15, 16+ = 20 | Exactly 1, 7, 8, 15 and 16 years; one day short of a year; no hire date; future hire date | 11 | Pass |
| `WorkingDaysTest` | Leave counts Monday to Friday only and skips public holidays | Mon–Wed = 3; Fri–Mon = 2; Sat–Sun = 0; Mon–Wed with a Tuesday holiday = 2; a yearly holiday applies every year; a weekend holiday isn't subtracted twice | 12 | Pass |
| `SettingTest` | Rollover settings HR can change | Defaults (off, max 5); saved values read back; saving twice updates instead of duplicating; a negative max becomes 0 | 5 | Pass |
| `EmployeeCodeTest` | Employee ID = department ID × 100000 + running number | First ID in department 1 is 100001; each department counts separately; a deleted employee's number isn't reused | 4 | Pass |

Working-day tests use a fixed week (Mon 2026-11-02 to Sun 2026-11-08), so results don't depend on the day the tests run.

### Bug tracking

Every defect found during review and testing is logged with an ID, its severity and its fix. All 26 are closed.

- **High:** a crash, data loss or a security hole.
- **Medium:** a wrong result or a missing function.
- **Low:** cosmetic or housekeeping.

| ID | Found | Description | Severity | How found | Fix | Fixed |
| --- | --- | --- | --- | --- | --- | --- |
| B26 | 2026-11-08 | API returned the code `http_error` instead of `forbidden` for some refused requests | Low | Running the API tests | Error handler maps every 403 / 404 to its proper code | 2026-11-08 |
| B25 | 2026-11-08 | HR had exactly the same rights as Admin, so an HR user could make themselves an Admin | High | Reviewing roles against the brief | `EmployeePolicy`: only an Admin can give the Admin role or edit / remove an Admin account | 2026-11-08 |
| B24 | 2026-11-08 | Creating several employees at once could give two of them the same Employee ID | Medium | Seeder test (factory made 2 at once) | The ID is assigned by the model at the moment each record is saved | 2026-11-08 |
| B23 | 2026-11-08 | PDF footer printed the page number on top of the footer text | Low | Reviewing generated PDFs | Page number moved to the right edge | 2026-11-08 |
| B22 | 2026-11-08 | Employee report printed the HR role as "Hr" | Low | Reviewing generated PDFs | Proper role labels (Employee, HR, Admin) | 2026-11-08 |
| B21 | 2026-11-08 | Employee report had no Pending column, so allowance, used and left didn't add up | Low | Reviewing generated PDFs | Pending column added | 2026-11-08 |
| B20 | 2026-11-08 | A department's only Head could be demoted, moved or deleted, leaving staff leave with nobody to approve it | Medium | Stakeholder review | Blocked while the department has other staff; a department with no Head sends leave straight to HR | 2026-11-08 |
| B19 | 2026-11-08 | Leave counted calendar days, so weekends and holidays used up allowance | Medium | Stakeholder review | Count Monday–Friday only; HR keeps a public-holiday list | 2026-11-08 |
| B18 | 2026-11-08 | Top allowance tier labelled "15+ years", but 15 years belongs to the 8–15 tier | Low | Writing unit tests | Label changed to "16+ years" | 2026-11-08 |
| B17 | 2026-11-08 | Two migrations used MySQL-only SQL, so 25 of 26 tests failed on SQLite | High | Running the test suite | Rewritten with Laravel's portable schema methods | 2026-11-08 |
| B16 | 2026-11-03 | No sample data for demos | Low | Code review | `DemoSeeder` added | 2026-11-08 |
| B15 | 2026-11-03 | Two tests out of date (expected `/` to show a page) | Low | Code review | Tests rewritten; suite expanded to 153 | 2026-11-08 |
| B14 | 2026-11-03 | App name still "Laravel" | Low | Code review | Renamed to Crewly | 2026-11-08 |
| B13 | 2026-11-03 | Default Laravel welcome page | Low | Code review | `/` redirects to login or dashboard | 2026-11-07 |
| B12 | 2026-11-03 | Default Laravel logo | Low | Code review | Crewly logo | 2026-11-07 |
| B11 | 2026-11-03 | Departments and Employees menu links shown to everyone | Medium | Code review | Shown to Admin and HR only | 2026-11-07 |
| B10 | 2026-11-03 | A Head could approve their own leave | High | Code review | A Head's own leave goes straight to HR | 2026-11-07 |
| B09 | 2026-11-03 | Head-only check on the approvals page was commented out | High | Code review | Check restored | 2026-11-07 |
| B08 | 2026-11-03 | Any signed-in user could create, edit or delete departments and employees | High | Code review | Admin/HR-only middleware | 2026-11-07 |
| B07 | 2026-11-03 | Renaming a department to an existing name caused a server error | Medium | Code review | Unique-name check with a clear message | 2026-11-08 |
| B06 | 2026-11-03 | Deleting a department also deleted its employees | High | Code review | Delete blocked while the department has employees | 2026-11-07 |
| B05 | 2026-11-03 | Public sign-up created users with no employee record; leave pages crashed for them | High | Code review | Sign-up removed (Admin/HR create accounts); pages made null-safe | 2026-11-07 |
| B04 | 2026-11-03 | An employee's role couldn't be changed after creation | Medium | Code review | Role field on Edit Employee; last Admin/HR protected | 2026-11-08 |
| B03 | 2026-11-03 | Choosing rank Admin or HR on edit caused a server error | High | Code review | Rank limited to Employee or Head | 2026-11-07 |
| B02 | 2026-11-03 | Rejection reasons were never saved (field-name typo) | Medium | Code review | Field name corrected | 2026-11-07 |
| B01 | 2026-11-03 | Every new user was saved as Employee, whatever role was chosen | High | Code review | Role made assignable | 2026-11-07 |

---

## 4.4.4.2 Integration Tests

All 132 integration tests pass. Each one sends a real HTTP request to the REST API, signed in as a given role (Sanctum). The request goes through the full stack: routes, middleware, FormRequest validation, controller, services, models and database. The test then checks the JSON response and what was saved. They are Laravel feature tests in `hr-system/hr-system/tests/Feature/`, and each test starts from an empty database.

| Test file | Module | What is verified | Tests | Result |
| --- | --- | --- | --- | --- |
| `Api/AuthApiTest` | Sign-in | SPA cookie login returns the profile and permissions; wrong password → structured 422; 401 shape; logout; API tokens issued, used and revoked; forgot-password doesn't reveal accounts; reset link points to the SPA and works; bad reset token refused; profile and password update; JSON 404 | 13 | Pass |
| `Api/AccountApiTest` | Accounts | No public sign-up; verification email sent (not to verified users); signed link verifies; forged or unsigned link refused; changing email needs re-verification | 6 | Pass |
| `Api/LeaveApiTest` | Leave flow | Submit returns 201 with working days; Employee → Head → HR → Approved over the API; business-rule codes; custom validation messages; Head/HR endpoints refuse employees; Head of another department can't approve; reject needs a reason; only the owner cancels; own list is paginated and private; HR filters | 12 | Pass |
| `Api/LeaveRulesApiTest` | Leave rules | Head's own leave and a Head-less department go to HR; weekends and holidays don't count; holiday-only and overlapping requests refused; cancelling frees the dates; sick leave only in the past and auto-approved; annual leave not in the past; professional leave unlimited; accounts without an employee record | 11 | Pass |
| `Api/StaffApiTest` | Departments, employees, settings | Staff endpoints refuse employees; department and employee CRUD; duplicate names; department with staff can't be deleted; dropdown list; filters and pagination; last Admin / last Head codes; HR can't be Head; leave history; settings round trip; holidays; dashboard scoped by role | 13 | Pass |
| `Api/EmployeeRulesApiTest` | Safeguards and roles | Last Admin / last HR can't be demoted or deleted; last Head can't be demoted or moved while the department has staff; **only an Admin can create, edit or remove Admins or grant the Admin role**; HR manages everyone else; unique email; demo seeder builds the company and re-runs safely | 14 | Pass |
| `Api/DataIntegrityApiTest` | Hierarchy, skills, soft deletes, factories | Parent departments; no loops; deleting a parent moves children up; soft delete cancels pending leave, keeps history, blocks sign-in, restores; restore needs the department; IDs never reused; deleted names point to restore; skills CRUD, levels, sync, filter, cascade; factories | 17 | Pass |
| `Api/NotificationsActivityAvatarTest` | Notifications, activity log, avatars | Each leave step notifies the right person (never the actor); rejection includes the reason; list / count / mark read; can't read someone else's notification; changes logged with who and old → new; passwords never logged; sign-ins logged with last login; skill changes logged; activity filters and access; avatar upload replaces the old file; unsafe files refused | 12 | Pass |
| `Api/HolidayImportTest` | Holiday import | CSV and real Excel files (date cells); dd/mm/yyyy and ";" CSVs; preview saves nothing; existing, duplicate and invalid rows reported and skipped; missing headings, empty files, wrong types and over 500 rows refused; employees can't import; both templates download and import as they are | 11 | Pass |
| `ReportTest` | CSV and PDF exports | Only Admin/HR can export; guests go to login; CSV rows and working days; exports use the same filters as the pages; date-range overlap; formula text neutralised; all three PDFs download | 13 | Pass |
| `LeaveRolloverTest` | Rollover | Off by default; unused days carry over up to HR's maximum; staff hired this year carry nothing; only HR or Admin can change it | 6 | Pass |
| `SpaShellTest` | The SPA | Every page path serves the React app; `/api` paths are not swallowed by it; the CSRF-cookie endpoint and health check work | 4 | Pass |

Defect B17 surfaced on the first run of the original suite: 25 of 26 tests failed until the migrations were made database-portable. When the app was rebuilt as a REST API, every check from the old page tests was moved to the API, so no rule lost its test.

---

## 4.4.4.3 System Test

The system test checks 21 complete user tasks in the real app, end to end, as each role would do them. It runs on MySQL with the demo data, in Chrome, against the production frontend build. These cases have not been run yet. Fill in the **Actual result** and **Result** (Pass / Fail) columns during the test session.

**Setup:** run `php artisan migrate:fresh --seed`, then `php artisan serve`, and open http://127.0.0.1:8000. Every demo account's password is `password`.

| ID | Account | Steps | Expected result | Actual result | Result |
| --- | --- | --- | --- | --- | --- |
| ST01 | admin@test.com | Log in | Dashboard shows company-wide staff, department and leave figures | | |
| ST02 | admin@test.com | Log in with a wrong password | Error message; user stays on the login page | | |
| ST03 | bobby@test.com | Request annual leave, Monday to Wednesday next week | Form shows "3 working days"; request saved as Pending Head | | |
| ST04 | bobby@test.com | Pick Friday to the following Monday | Form shows "2 working days" | | |
| ST05 | bobby@test.com | Pick a Saturday to Sunday | Weekend warning shown; Submit is disabled | | |
| ST06 | james@test.com (Finance Head) | Approve Bobby's request from ST03 | Status becomes Pending HR; request appears in Leave History | | |
| ST07 | hr@test.com | Approve the same request in All Leave | Status becomes Approved; Bobby's balance shows 3 more days used and 3 fewer pending | | |
| ST08 | james@test.com | Reject a pending request, giving a reason | Status becomes Rejected; the employee sees the reason | | |
| ST09 | kevin@test.com | Cancel a pending request, giving a reason | Status becomes Cancelled | | |
| ST10 | hr@test.com, then bobby@test.com | HR adds a weekday holiday in Leave Settings; Bobby requests a range that includes it | The holiday is not counted in the working days | | |
| ST11 | hr@test.com, then jackie@test.com | HR turns rollover on (max 5); Jackie opens her dashboard | Annual balance includes 5 carried-over days | | |
| ST12 | admin@test.com | Create an employee, then log in as them | An employee ID is assigned automatically; the new account can log in | | |
| ST13 | admin@test.com | Change an employee's role to HR, then log in as them | HR menu items (All Leave, Leave Settings) appear | | |
| ST14 | admin@test.com | Try to delete James, the only Finance Head | Blocker message; James is not deleted | | |
| ST15 | bobby@test.com | Type /employees into the address bar | Sent back to the dashboard with the message “You don't have access to that page.” | | |
| ST16 | hr@test.com | In All Leave, filter Status = Approved, then click Export CSV and Export PDF | Both files contain only approved requests; the CSV opens in Excel with names shown correctly | | |
| ST17 | bobby@test.com, then james@test.com | Bobby requests leave; James opens the bell in the top bar | The bell shows an unread count; the notification opens the Approvals page | | |
| ST18 | hr@test.com | Delete an employee, open Employees → Deleted, click Restore | The person disappears from the list, then comes back with their leave history | | |
| ST19 | hr@test.com | Add a department "Concierge" inside "Operations"; try to put Operations inside Concierge | Concierge shows indented under Operations; Operations isn't offered as a choice inside Concierge | | |
| ST20 | bobby@test.com | Profile → upload a JPG photo; then try an SVG or a 5 MB file | The photo shows in the top bar; the SVG and the large file are refused with a message | | |
| ST21 | hr@test.com | Edit Bobby's skills (add Excel – Expert); open Activity log | The change is listed with who made it and the skill added | | |

---

## 4.4.4.4 Security Tests

All 31 automated security tests pass. They check five things:

- who can do what (access control and roles)
- that the system can never lose its key accounts
- that sign-in and accounts are safe
- that uploaded files are safe
- that exported files are safe to open

The tests are part of the integration suite. System tests ST15 and ST20 repeat two of the checks by hand in the browser.

| # | Check | Expected behaviour | Test file | Result |
| --- | --- | --- | --- | --- |
| **Access control and roles** | | | | |
| 1 | Employee opens departments, employees, settings, skills or the activity log | 403 `forbidden` | `StaffApiTest`, `DataIntegrityApiTest`, `NotificationsActivityAvatarTest` | Pass |
| 2 | Employee opens Head or HR approval endpoints | 403 `forbidden` | `LeaveApiTest` | Pass |
| 3 | Employee changes leave settings or adds a holiday | 403; nothing changed | `LeaveRolloverTest`, `StaffApiTest` | Pass |
| 4 | Employee downloads any report | 403 | `ReportTest` | Pass |
| 5 | Head approves leave from another department | 403 | `LeaveApiTest` | Pass |
| 6 | Head approves their own leave | Not possible: it goes straight to HR | `LeaveRulesApiTest` | Pass |
| 7 | Employee cancels someone else's request | 403 | `LeaveApiTest` | Pass |
| 8 | User reads someone else's notification | 404 | `NotificationsActivityAvatarTest` | Pass |
| 9 | HR creates an Admin account | 403 “Only an Admin can give someone the Admin role.” | `EmployeeRulesApiTest` | Pass |
| 10 | HR promotes themselves (or anyone) to Admin | 403; role unchanged | `EmployeeRulesApiTest` | Pass |
| 11 | HR edits or deletes an Admin account | 403 | `EmployeeRulesApiTest` | Pass |
| **Key-account protection** | | | | |
| 12 | Demote the last Admin | Refused (`last_admin`) | `EmployeeRulesApiTest` | Pass |
| 13 | Demote or delete the last HR | Refused (`last_hr`) | `EmployeeRulesApiTest` | Pass |
| 14 | Delete the last Admin | Refused | `StaffApiTest` | Pass |
| 15 | Make an HR or Admin account a Head as well | Refused (keeps approval duties separate) | `StaffApiTest` | Pass |
| **Sign-in and accounts** | | | | |
| 16 | Request without a session or token | 401 `unauthenticated` | `AuthApiTest` | Pass |
| 17 | Guest opens a report link | Redirected to the sign-in page | `ReportTest` | Pass |
| 18 | Sign in with a wrong password | 422; stays signed out | `AuthApiTest` | Pass |
| 19 | Deleted (soft-deleted) user signs in | Refused | `DataIntegrityApiTest` | Pass |
| 20 | Revoked API token used again | 401 | `AuthApiTest` | Pass |
| 21 | Public sign-up | 404; no account created | `AccountApiTest` | Pass |
| 22 | Forgot-password reveals whether an email has an account | Same answer either way | `AuthApiTest` | Pass |
| 23 | Reset password with a fake token | Refused | `AuthApiTest` | Pass |
| 24 | Change password without the correct current one | Refused | `AuthApiTest` | Pass |
| 25 | Verify email with a forged or unsigned link | 403; not verified | `AccountApiTest` | Pass |
| 26 | Passwords written to the activity log | Never: shown as “(hidden)” | `NotificationsActivityAvatarTest` | Pass |
| **Uploads** | | | | |
| 27 | Upload an SVG (can contain scripts) as a profile photo | Refused | `NotificationsActivityAvatarTest` | Pass |
| 28 | Upload a PHP file renamed to `.jpg` | Refused (content checked, not just the name) | `NotificationsActivityAvatarTest` | Pass |
| 29 | Upload a file over 2 MB or a tiny image | Refused | `NotificationsActivityAvatarTest` | Pass |
| 30 | Uploaded file keeps its original name | Never: stored under a random name | `NotificationsActivityAvatarTest` | Pass |
| **Safe exports** | | | | |
| 31 | A leave reason typed as a spreadsheet formula (e.g. `=HYPERLINK(...)`) | Exported as plain text, so it can't run when opened in Excel (CSV injection) | `ReportTest` | Pass |

The framework also provides protections that were not tested separately:

- CSRF protection on the SPA's cookie session (Sanctum, `X-XSRF-TOKEN`)
- bcrypt password hashing
- parameter binding, which blocks SQL injection (Eloquent)
- output escaping, which blocks cross-site scripting (React and Blade)
- rate limiting on sign-in and password endpoints
