# Deploying Crewly

Crewly is **one Laravel app** that serves both the REST API (`/api/*`) and the React SPA from the same domain. That counts as deploying "both API and frontend", and it keeps sign-in simple, because the cookie session only works on one domain. You need:

- a host that runs **PHP 8.2+** (8.4 recommended)
- a **MySQL 8** database
- a way to **build the frontend** with Node 20+ during deployment
- **persistent storage** for profile photos: a mounted volume, or S3-compatible storage

The app itself lives in `hr-system/hr-system/` inside the repository. Set it as the **root directory** on your host.

---

## 1. Choose a host

Any of these suit a Laravel + MySQL app. **Check each one's current pricing and free tier before signing up**, because they change often.

| Host | Why it fits | Watch out for |
| --- | --- | --- |
| **Railway** | Detects Laravel automatically, offers MySQL in the same project, supports persistent volumes | Free usage is limited or trial-based |
| **Render** | Simple web services, free tier for web apps | Its built-in database is Postgres; MySQL needs an external service or a Docker setup. Free web services sleep when idle |
| **Laravel Cloud** | Made by the Laravel team, minimal setup | Check what its free tier includes |
| **A VPS** (DigitalOcean, etc.) | Full control | You maintain the server yourself (nginx, PHP, MySQL, HTTPS) |

**You create the account yourself.** Then share which host you picked, and the remaining steps can be filled in for that host.

---

## 2. Environment variables

Copy the values from [`hr-system/hr-system/.env.production.example`](hr-system/hr-system/.env.production.example) into the host's environment-variable settings. The important ones:

| Variable | Value |
| --- | --- |
| `APP_ENV` / `APP_DEBUG` | `production` / `false`. Never `true` on a public site, because it would show error details |
| `APP_KEY` | Run `php artisan key:generate --show` locally and paste the result. Use one key and never change it |
| `APP_URL` | `https://your-domain`, with no trailing slash |
| `DB_*` | The MySQL connection details from your host |
| `SESSION_SECURE_COOKIE` | `true` |
| `SANCTUM_STATEFUL_DOMAINS` | Your domain without `https://`, e.g. `crewly.up.railway.app` |
| `LOG_CHANNEL` | `stderr`, so logs appear in the host's log viewer |
| `AVATAR_DISK` | `public` with a volume (see step 4), or `s3` |
| `MAIL_MAILER` | `log` until you set up a mail service. Reset and verification links then appear in the logs |
| `DEMO_PASSWORD` | A **private** password for the demo accounts, if you seed demo data. The README shows `password` for local use |

---

## 3. Build and start commands

**Build** (runs on every deploy):

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

**Before starting** (also on every deploy, once the database is reachable):

```bash
php artisan migrate --force          # apply new migrations (never deletes data)
php artisan storage:link             # make profile photos viewable
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Start:** most PHP hosts run nginx with PHP-FPM, pointing the web root at `public/`. On hosts that just run a command, use:

```bash
php artisan serve --host=0.0.0.0 --port=$PORT
```

**Health check path:** `/up`

**Demo data:** run this **once**, to give the examiners a company to look at:

```bash
php artisan db:seed --force          # uses DEMO_PASSWORD for the demo accounts
```

Don't run `migrate:fresh` on the live site, because it deletes everything.

---

## 4. Profile photos (persistent storage)

Most hosts **wipe the app's disk on every redeploy**. Photos are saved in `storage/app/public`, so pick one of these:

- **A volume:** mount a persistent volume at `hr-system/hr-system/storage/app/public` and keep `AVATAR_DISK=public`.
- **Cloud storage:** run `composer require league/flysystem-aws-s3-v3`, set `AVATAR_DISK=s3`, and fill in the `AWS_*` variables. This works with AWS S3, Cloudflare R2 and other S3-compatible services.

---

## 5. After the first deploy: checks

1. `https://your-domain/up` shows a success page.
2. `https://your-domain/` shows the Crewly sign-in screen.
3. Sign in as `admin@test.com` with your `DEMO_PASSWORD`. The dashboard and charts should load.
4. Refresh the page. You should stay signed in, which shows the cookie session and HTTPS are working.
5. Upload a profile photo, redeploy, and check the photo is still there.
6. As HR, open All Leave and click **Export CSV**. A file should download.
7. In Postman, set the environment's `baseUrl` to `https://your-domain` and run *Auth → Get token as Admin*.

Then add the live URLs to the table at the top of `README.md`.

---

## 6. Common problems

| Symptom | Cause and fix |
| --- | --- |
| Sign-in works, but every request then gives 401, or a refresh signs you out | `SANCTUM_STATEFUL_DOMAINS` doesn't match the domain exactly, or `SESSION_SECURE_COOKIE=true` is set without HTTPS |
| Error 419 "session expired" right away | `APP_URL` doesn't match the real domain. Clear and rebuild the config cache after changing variables (`php artisan config:clear && php artisan config:cache`) |
| Pages load without styles, or the console shows 404s for `/build/...` | `npm run build` didn't run during the build step |
| Error 500 with no details | Check the host's logs (`LOG_CHANNEL=stderr`). The most common cause is a missing `APP_KEY` or wrong `DB_*` values |
| Profile photos disappear after a redeploy | There's no persistent storage. See step 4 |
| The email-verification link says "invalid" | `APP_URL` isn't the https address. The app already trusts the host's proxy, so this is almost always the setting |
| Changed variables have no effect | The config is cached. Redeploy, or run `php artisan config:cache` again |
