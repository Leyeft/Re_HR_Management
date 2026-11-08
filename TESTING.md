# Testing — Crewly HR Management System

_Last updated: 2026-11-08_

Crewly is tested at four levels: unit, integration, system and security. All **111 automated tests pass**: 32 unit tests and 79 integration tests. System tests are run by hand in the browser.

```
cd hr-system\hr-system
php artisan test                      # everything (~5 seconds)
php artisan test --testsuite=Unit     # unit tests only
php artisan test --filter=LeaveFlow   # one file
```

---

## 4.4.3 Testing Plan

### Scope

Testing covers the features the committee will assess:

- login and accounts
- employee and department management
- leave requests, with two-step approval (Head, then HR)
- leave allowances, rollover and public holidays
- CSV and PDF reports
- role-based access control

Load and stress testing is out of scope, because the system serves one company's staff (tens of users, not thousands).

### Test levels

| Level | What is tested | How | Count |
| --- | --- | --- | --- |
| Unit | Single business rules (allowance tiers, working days, settings, employee IDs) | PHPUnit, automated | 32 |
| Integration | A full request through routes, middleware, controllers and the database | Laravel feature tests, automated | 79 |
| System | Complete user tasks in the real app, from login to result | Manual, in the browser | 16 cases |
| Security | Who can see and do what; protection of key accounts; safe exports | Part of the integration tests, plus manual checks | 22 |

### Environment

| Item | Automated tests | System tests |
| --- | --- | --- |
| Server | PHP 8.4, Laravel 12.42 | Same, via `php artisan serve` |
| Database | SQLite in memory, rebuilt for every test | MySQL 8.4, loaded with the demo seeder |
| Frontend | Not needed | React (Inertia) production build, Chrome |
| Command | `php artisan test` | `php artisan migrate:fresh --seed` |

The automated suite runs in about 5 seconds, so it is re-run after every change. A change is only committed when every test passes.

### Schedule

| Date | Activity | Status |
| --- | --- | --- |
| 2026-11-03 | Code review of the converted React version; 16 issues logged (B01–B16) | Done |
| 2026-11-07 | Fixes for B01–B03, B05, B06 and B08–B13 | Done |
| 2026-11-08 | Fixes for B04, B07 and B14–B23; unit, integration and report tests written; 111 of 111 pass | Done |
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

Every defect found during review and testing is logged with an ID, its severity and its fix. All 23 are closed.

- **High:** a crash, data loss or a security hole.
- **Medium:** a wrong result or a missing function.
- **Low:** cosmetic or housekeeping.

| ID | Found | Description | Severity | How found | Fix | Fixed |
| --- | --- | --- | --- | --- | --- | --- |
| B23 | 2026-11-08 | PDF footer printed the page number on top of the footer text | Low | Reviewing generated PDFs | Page number moved to the right edge | 2026-11-08 |
| B22 | 2026-11-08 | Employee report printed the HR role as "Hr" | Low | Reviewing generated PDFs | Proper role labels (Employee, HR, Admin) | 2026-11-08 |
| B21 | 2026-11-08 | Employee report had no Pending column, so allowance, used and left didn't add up | Low | Reviewing generated PDFs | Pending column added | 2026-11-08 |
| B20 | 2026-11-08 | A department's only Head could be demoted, moved or deleted, leaving staff leave with nobody to approve it | Medium | Stakeholder review | Blocked while the department has other staff; a department with no Head sends leave straight to HR | 2026-11-08 |
| B19 | 2026-11-08 | Leave counted calendar days, so weekends and holidays used up allowance | Medium | Stakeholder review | Count Monday–Friday only; HR keeps a public-holiday list | 2026-11-08 |
| B18 | 2026-11-08 | Top allowance tier labelled "15+ years", but 15 years belongs to the 8–15 tier | Low | Writing unit tests | Label changed to "16+ years" | 2026-11-08 |
| B17 | 2026-11-08 | Two migrations used MySQL-only SQL, so 25 of 26 tests failed on SQLite | High | Running the test suite | Rewritten with Laravel's portable schema methods | 2026-11-08 |
| B16 | 2026-11-03 | No sample data for demos | Low | Code review | `DemoSeeder` added | 2026-11-08 |
| B15 | 2026-11-03 | Two tests out of date (expected `/` to show a page) | Low | Code review | Tests rewritten; suite expanded to 111 | 2026-11-08 |
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

All 79 integration tests pass. Each one sends a real HTTP request through the full stack: routes, middleware, controller, models and database. It then checks the response and what was saved. They are Laravel feature tests in `hr-system/hr-system/tests/Feature/`, and each test starts from an empty database.

| Test file | Module | What is verified | Tests | Result |
| --- | --- | --- | --- | --- |
| `LeaveFlowTest` | Leave requests and approval | Annual leave moves Employee → Head → HR → Approved, and HR can't skip the Head; a Head from another department can't approve; a Head's own leave goes to HR; a department with no Head sends leave to HR; requests over the allowance, overlapping requests and weekend- or holiday-only requests are refused; sick leave is recorded without approval; only the owner can cancel; HR manages public holidays | 17 | Pass |
| `EmployeeManagementTest` | Employees | Admin creates an employee and an ID is assigned; role can be changed on edit; HR or Admin can't also be Head; last Admin, last HR and a department's last Head are protected; the demo seeder runs and re-runs without duplicates | 13 | Pass |
| `ReportTest` | CSV and PDF exports | Only Admin/HR can export; CSV has one row per request with working days; exports use the page's filters (department, status, type, date range); the All Leave page applies the same filters; a "To" date before the "From" date is refused; formula text is neutralised; all three PDFs download | 13 | Pass |
| `LeaveRolloverTest` | Rollover | Off by default; unused days carry over up to HR's maximum; staff hired this year carry nothing; only HR or Admin can change it | 6 | Pass |
| `DepartmentTest` | Departments | HR creates a department; a duplicate name is refused on rename; a department with employees can't be deleted | 5 | Pass |
| `ProfileTest` | Profile | Profile page loads; details update; account delete needs the correct password | 5 | Pass |
| Auth tests (6 files) | Login and passwords | Login and logout; wrong password refused; password reset and update; email verification; public sign-up closed | 18 | Pass |
| `ExampleTest` | Home page | Guests go to login; signed-in users go to the dashboard | 2 | Pass |

Defect B17 surfaced on the first run of this suite: 25 of 26 tests failed until the migrations were made database-portable.

---

## 4.4.4.3 System Test

The system test checks 16 complete user tasks in the real app, end to end, as each role would do them. It runs on MySQL with the demo data, in Chrome, against the production frontend build. These cases have not been run yet. Fill in the **Actual result** and **Result** (Pass / Fail) columns during the test session.

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
| ST15 | bobby@test.com | Type /employees into the address bar | 403 Forbidden page | | |
| ST16 | hr@test.com | In All Leave, filter Status = Approved, then click Export CSV and Export PDF | Both files contain only approved requests; the CSV opens in Excel with names shown correctly | | |

---

## 4.4.4.4 Security Tests

All 22 automated security tests pass. They check four things:

- who can do what (access control)
- that the system can never lose its key accounts
- that sign-in is safe
- that exported files are safe to open

The tests are part of the integration suite. System test ST15 repeats one of the checks by hand in the browser.

| # | Check | Expected behaviour | Test file | Result |
| --- | --- | --- | --- | --- |
| **Access control** | | | | |
| 1 | Regular employee opens department management | 403 Forbidden | `DepartmentTest` | Pass |
| 2 | Regular employee opens employee management | 403 Forbidden | `EmployeeManagementTest` | Pass |
| 3 | Regular employee changes leave settings | 403 Forbidden; setting unchanged | `LeaveRolloverTest` | Pass |
| 4 | Regular employee adds a public holiday | 403 Forbidden | `LeaveFlowTest` | Pass |
| 5 | Regular employee downloads any report | 403 Forbidden | `ReportTest` | Pass |
| 6 | Head approves leave from another department | 403 Forbidden | `LeaveFlowTest` | Pass |
| 7 | HR approves before the Head has | 403 Forbidden | `LeaveFlowTest` | Pass |
| 8 | Head approves their own leave | Not possible: it goes straight to HR | `LeaveFlowTest` | Pass |
| 9 | Employee cancels someone else's request | 403 Forbidden | `LeaveFlowTest` | Pass |
| **Key-account protection** | | | | |
| 10 | Demote the last Admin | Refused with a message | `EmployeeManagementTest` | Pass |
| 11 | Demote the last HR | Refused with a message | `EmployeeManagementTest` | Pass |
| 12 | Delete the last Admin | Refused with a message | `EmployeeManagementTest` | Pass |
| 13 | Make an HR or Admin account a Head as well | Refused (keeps approval duties separate) | `EmployeeManagementTest` | Pass |
| **Authentication** | | | | |
| 14 | Guest opens the app or a report link | Redirected to login | `ExampleTest`, `ReportTest` | Pass |
| 15 | Log in with a wrong password | Refused; stays signed out | `AuthenticationTest` | Pass |
| 16 | Open the sign-up page | 404: public sign-up is closed | `RegistrationTest` | Pass |
| 17 | Post a sign-up form directly | 404; no account created | `RegistrationTest` | Pass |
| 18 | Delete own account with a wrong password | Refused | `ProfileTest` | Pass |
| 19 | Change password without the correct current one | Refused | `PasswordUpdateTest` | Pass |
| 20 | Confirm password with a wrong one | Refused | `PasswordConfirmationTest` | Pass |
| 21 | Verify email with a forged link | Not verified | `EmailVerificationTest` | Pass |
| **Safe exports** | | | | |
| 22 | A leave reason typed as a spreadsheet formula (e.g. `=HYPERLINK(...)`) | Exported as plain text, so it can't run when opened in Excel (CSV injection) | `ReportTest` | Pass |

The framework also provides protections that were not tested separately:

- CSRF tokens on every form (Laravel)
- bcrypt password hashing
- parameter binding, which blocks SQL injection (Eloquent)
- output escaping, which blocks cross-site scripting (React and Blade)
