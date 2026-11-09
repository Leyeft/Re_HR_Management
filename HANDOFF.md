# Handoff — Crewly HR Management System

_Last updated: 2026-11-09_

Read this first when picking the project up. It says where things are, what's done, what's left, and the decisions behind them. For how the code works, read [docs/HOW_IT_WORKS.md](docs/HOW_IT_WORKS.md).

---

## 1. Where we are

| | |
| --- | --- |
| **App** | Crewly, a Laravel 12 **REST API** + **React 18 SPA** (React Router, Redux Toolkit, Axios) + MySQL, with Sanctum auth |
| **Code** | `hr-system/hr-system/` inside this repo |
| **Repo / branch** | `github.com/Leyeft/Re_HR_Management`, branch **`main`** (git remote `personal`). Local `main` tracks `personal/main`. `react-frontend` is an old branch, identical to an earlier `main`; it can be deleted |
| **Live site** | **https://rehrmanagement-production.up.railway.app** (Railway). It redeploys automatically on every push to `main` |
| **Tests** | **184 passing**: 31 unit + 153 API feature tests (`php artisan test`) |
| **Course brief** | `hr-system/hr-system/public/logo/IT 341 Final Project 1S2025-2026 1.docx`. **Deliberately left untracked**: never commit it, because `public/` is served to the web |
| **Estimated grade vs. brief** | About 83–90%, if the defense goes well (see section 5) |

**State at the end of the last session:** everything was committed **and pushed** (last commit `4011b7d`, docs + code comments). Railway was redeploying with the holiday import, Clear all, and the docs.

---

## 2. Run it locally

```bash
cd C:\laragon\www\HR_Management\final-project-Leyeft-1\hr-system\hr-system
php artisan serve          # → http://127.0.0.1:8000
npm run dev                # second terminal, only while editing React (or: npm run build)
php artisan test           # 184 tests, SQLite in memory, ~1 min
```

- **Database:** MySQL `hr_system` (Laragon, root, no password). All migrations have run locally.
- **Local data:** this is **older hand-made data**, not the demo seeder's. For example, `itsupport@test.com` has password `12345678`, while most others use `password`. To get the clean demo company run `php artisan migrate:fresh --seed`, which **wipes local data**.
- **PHP:** Laragon PHP 8.4. The **`zip` extension was enabled** in `C:\laragon\bin\php\php-8.4.22-Win32-vs17-x64\php.ini` (line 813) so Excel import works. If `php artisan serve` was started before an ini change, restart it.
- **Timezone:** `APP_TIMEZONE=Asia/Bangkok` (GMT+7).

**Demo accounts** (from `DemoSeeder`): admin@test.com (Admin), hr@test.com (HR), james@test.com (Head of Finance), itsupport@test.com (Head of IT), housekeep@test.com, maintain@test.com (Heads), bobby@ / fiona@ (Finance), jackie@ / kevin@ (IT), lara@ (Housekeeping), noel@ (Maintenance), plus 8 factory-made staff with random emails.
- **Locally:** the password is `password`.
- **Live:** the password is the private `DEMO_PASSWORD` set in Railway Variables. The user knows it; it is **not** in this repo or the chat.

---

## 3. Live deployment (Railway)

These were set up by the user in the Railway dashboard, with guidance. The full guide is [DEPLOYMENT.md](DEPLOYMENT.md).

- **Services:** the app (from GitHub), and **MySQL** (service name `MySQL`).
- **App settings:**
  - Root Directory `/hr-system/hr-system`
  - branch `main`
  - healthcheck `/up`
  - domain `rehrmanagement-production.up.railway.app`
  - volume mounted at `/app/storage/app/public`, so profile photos persist
- **Variables:**
  - `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` (private), `APP_URL=https://rehrmanagement-production.up.railway.app`
  - `DB_CONNECTION=mysql`, `DB_URL=${{MySQL.MYSQL_URL}}`
  - `SESSION_SECURE_COOKIE=true`, `SANCTUM_STATEFUL_DOMAINS=<domain>`
  - `LOG_CHANNEL=stderr`, `MAIL_MAILER=log`, `AVATAR_DISK=public`
  - `DEMO_PASSWORD` (private)
  - `RAILPACK_PHP_VERSION=8.4`
- **Pre-Deploy Command:** `php artisan migrate --force && php artisan db:seed --force`. It was used for the first deploy. It can be shortened to `php artisan migrate --force`, but leaving `db:seed` is harmless: since 2026-11-09 the DemoSeeder **only runs on an empty database** (no accounts). Before that fix, every deploy re-created renamed departments, deleted skills and cleared holidays. The user had renamed "IT Support" to "IT" and got a new empty "IT Support" back, which they should delete in the app.
- **Railpack** auto-detects Laravel, runs Composer, `npm run build`, migrations and `storage:link` on start, and serves with FrankenPHP. Railway's `railway.json` config files are **deprecated**, which is why all settings are in the dashboard.
- **Fixed during deploy:** `composer.json` now requires `php ^8.4`, because the locked packages need 8.4. This was merged from Railway's PR #1. It also declares `ext-pdo_mysql`, `ext-gd` and `ext-zip`, so Railpack installs them.
- **Verified live:** `/up` 200, SPA loads, API 401 shape, DB connected, secure cookies, http→https.
- **Confirmed by the user (2026-11-09):** signing in on the live site with the demo password works.

---

## 4. What's done

The SPA rebuild was done in phases, after the teacher rejected Inertia on 2026-11-08:

| Phase | What | Status |
| --- | --- | --- |
| 1 | REST API: Sanctum SPA auth, `role:` / `head` middleware, `{code,message,errors}` JSON errors, Resources, FormRequests, services | ✅ |
| 2 | SPA shell: React Router, Redux Toolkit, Axios client, private routes, toasts, layout | ✅ |
| 3 | All pages moved to the SPA | ✅ |
| 4 | Department hierarchy (self-referencing `parent_id`), skills pivot with a level, soft deletes + restore, factories | ✅ |
| 5 | In-app notifications (bell, 30 s polling), activity log, last login, avatar upload | ✅ |
| 6 | SPA at the site root; Inertia, Breeze and Ziggy removed; email verification on the API; web tests moved to the API | ✅ |
| 7a | Docs: README, Postman collection, TESTING.md, DEPLOYMENT.md, HOW_IT_WORKS.md; Admin vs HR (`EmployeePolicy`); API tokens | ✅ |
| 7b | Deployed to Railway | ✅ |

**Features added after the rebuild (2026-11-08/09):**
- **Salary:** monthly base salary (THB) on each employee (`employees.salary`, nullable). Seen by Admin, HR and the person themselves; **Heads don't see it** (user's choice). `EmployeeResource` leaves the key out for anyone else, checked per employee through `EmployeePolicy::viewSalary`. Changed only by Admin/HR via `editSalary`: never your own, and HR can't change an Admin's. It is in the Employees CSV (reports are Admin/HR only) but not in the PDF. DemoSeeder fills empty demo salaries. Changes appear in the activity log with old and new values (Admin/HR only). This is not full payroll: no tax, deductions or payslips.
- **Dark mode:** a Light / Dark / System switch in the user menu (and the phone menu). The choice is saved in the browser (`localStorage` key `crewly-theme`) by `store/themeSlice.js`. It works by setting `data-theme="dark"` on `<html>`; `app.css` holds a second set of colour values for it. An inline script in `spa.blade.php` applies the choice before the page paints, so there is no flash.
- **Leave rules:** working days (weekends and holidays skipped); allowance by years of service (3/10/15/20); sick leave with a 30-day cap, auto-approved; professional leave unlimited; HR-controlled rollover with a maximum; overlap check; cancel with a reason.
- **Holidays:** plain dates, uploaded **each year**. There's **no "recurring" flag any more**; it was removed by a migration because dates change yearly.
  - **Import from Excel/CSV** (`POST /api/holidays/import`, `dry_run=1` for a preview) with a downloadable template (`/templates/holidays.{xlsx,csv}`).
  - Upload rule (user's choice): **same date → name replaced; new date → added; other saved dates kept**.
  - **Clear all** (`DELETE /api/holidays`). Holidays are listed grouped by year.
- **Reports:** CSV/PDF of All Leave and Employees (same filters as the page); per-employee PDF leave record.
- **Production prep:** trusted proxies, https forced in production, configurable `AVATAR_DISK`, `DEMO_PASSWORD` via config, `.env.production.example`.
- **Code comments:** every source file has a header comment; the Employee model has numbered sections; unused leftovers were removed.

**Docs in the repo:**
- [README.md](README.md): features, architecture, install, roles table, endpoints, ERD
- [TESTING.md](TESTING.md): plan, unit/integration/system/security tests, bug log B01–B26
- [DEPLOYMENT.md](DEPLOYMENT.md)
- [docs/HOW_IT_WORKS.md](docs/HOW_IT_WORKS.md): study guide and defense Q&A
- [docs/postman/](docs/postman/): collection + environment, token auth

---

## 5. What's left / open items

**For the user:**
- **Defense prep:** read `docs/HOW_IT_WORKS.md`, especially sections 3 (a request traced through the files) and 7 (questions). The brief says "unable to explain own code: −25%", and live code changes may be requested.
- **Click through the live site** and check the screens in both light and dark mode.
- **Run the system test cases** by hand (ST01–ST21 in TESTING.md) and fill in the Result column.
- **GitHub Classroom repo:** the brief's deliverable is the Classroom link (`origin` remote), but **pushing there fails with 403**, and it only has the old Blade version. Ask the teacher to fix access, or to accept the personal repo.

**Suggested next tasks (not done yet):**
- **Certificate uploads** (user request, 2026-11-09, parked as future development; it is in the README's "Future development" section). Before building it, ask the user about the open questions listed there. Storage matters: the Railway volume only covers `storage/app/public`, so private files would be lost on redeploy unless the volume mount moves to `/app/storage/app` or the files go in the database.
- Set `baseUrl` in a Postman environment for the live site (the README already has the live URLs).
- Run **Laravel Pint** (PSR-12 formatting; the brief names PSR-12). Not done yet.
- **Real email** on the live site: the code is ready (2026-11-09). The `brevo` mailer sends over HTTPS, because Railway blocks SMTP on Hobby. The user still has to create a Brevo account, verify a sender, and set `MAIL_MAILER=brevo`, `BREVO_API_KEY` and `MAIL_FROM_ADDRESS` in Railway (DEPLOYMENT.md, section 5). Until then mail goes to the log. **Parked (2026-11-09):** Brevo sign-up got stuck at the SMS phone-verification step (the code never arrived). The user will retry later; meanwhile email stays on `log`, and the flows are demoed by copying the link from `storage/logs/laravel.log`.
- **Registration:** the brief lists "Registration", but public sign-up was removed by design (HR creates accounts). The options are to ask the teacher, or to add a "Request an account" form that HR approves.
- **React version:** the brief says "React > 18"; the app uses 18.2. Upgrading to 19 is low risk if needed.
- **Dark mode:** done (2026-11-09) but **not yet checked by eye**. Ask the user to look at every page in dark mode.
- **Online Testing doc** (claude.ai artifact from an early session): **outdated**. TESTING.md in the repo is current.
- `react-frontend` branch: can be deleted.

---

## 6. Key decisions and gotchas

- **Inertia is not allowed** (teacher), so the app must stay a REST API + SPA. Don't reintroduce Inertia or Blade pages.
- **Redux Toolkit** was chosen for global state. Core data lives in slices; only form typing is local.
- **Closed system:** there's no public sign-up. Admin/HR create accounts.
- **Roles:**
  - `users.role` is employee, hr or admin (system access).
  - `employees.rank` is employee or head (department).
  - HR and Admin can't be Heads.
  - **Only an Admin manages Admin accounts.**
- **Safeguards:** the system always keeps at least one Admin and one HR; a department with staff always has a Head. A department without a Head sends leave straight to HR.
- **Soft deletes** on users, employees and departments; Employee IDs are never reused.
- **Holidays:** no recurring flag; uploads replace same-date names only (not whole date ranges, which was explicitly decided).
- **Email:** only password reset and verification are emailed; everything else is in-app notifications (user's choice). Brevo over HTTPS, not SMTP, because Railway blocks SMTP. The user decided **not** to restrict password reset to verified emails.
- **Pushing:** push to `personal` (`Re_HR_Management`). Ask the user before pushing. Never push to `origin` (Classroom) without the user asking.
- **Tooling notes for the next session:**
  - The `gh` CLI isn't installed.
  - Inline Python heredocs in Bash mangle backslashes in PHP namespaces. Use script files or the Edit tool.
  - The Chrome extension wasn't connected, so the UI has **never been visually checked** by the assistant. Ask the user to check screens.

---

## 7. Branding and theme

- **Logo:** "Crewly" SVGs in `hr-system/hr-system/public/logo/` (`crewly-logo.svg`, `crewly-logo-on-dark.svg`, icon, favicon), rendered by `resources/js/Components/ApplicationLogo.jsx` (`dark` prop for the navy bar).
- **Colours** are defined once as CSS variables in `resources/css/app.css` (`:root`) and exposed to Tailwind (`primary`, `accent`, `success`, `warning`, `danger`, `info`, `bg`, `surface`, `line`, `content`, `muted`). To re-theme, edit the hex values in `app.css` only.
- **Dark mode** colours are in the same file under `:root[data-theme="dark"]`. Use the tokens, never `bg-white` or `gray-*`: use `bg-surface` for cards, `text-primary-text` for brand-coloured text (`bg-primary` is for fills with white text), `bg-nav` for the top bar, and `text-on-status` on solid green/red buttons.

```css
--color-primary: #12305A;  --color-accent: #2EC4B6;  --color-accent-text: #0B7A72;
--color-bg: #F4F7FA;       --color-text: #1A2B3C;    --color-text-muted: #4A5D73;
--color-success: #187A46;  --color-warning: #9A5B00; --color-danger: #C93A3A;  --color-info: #2F6FCC;
```

---

## 8. History

- **2025-12-15:** original Blade version, in the history before commit `968c3b2`.
- **2026-11-02:** converted to React + Inertia.
- **2026-11-03:** code review; 16 issues logged.
- **2026-11-07:** fixes and leave features.
- **2026-11-08:** the teacher rejected Inertia; rebuilt as a REST API + SPA (phases 1–7); deployed to Railway.
- **2026-11-09:** holiday import / Clear all / no recurring; docs and comments.

The full bug log is in TESTING.md (B01–B26). Every change is in `git log`.
