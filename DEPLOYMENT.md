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

## Railway: step by step (the host used for Crewly)

Railway builds the app with **Railpack**, which recognises Laravel by its `artisan` file and does most of the work automatically:
- installs Composer packages
- installs npm packages and runs `npm run build`
- caches the config and routes
- serves `public/` with FrankenPHP
- on every start, **runs `php artisan migrate --force` and `storage:link`**

So you don't need a Dockerfile or a custom start command. The settings below are all in the Railway dashboard, because Railway's `railway.json` config files are deprecated.

**1. Add the database.** In your Railway project, click **+ Create → Database → MySQL**. Keep the service name **MySQL**; the variables in step 3 refer to it by that name.

**2. App service settings.** Click the service connected to GitHub, then **Settings**:

| Setting | Value |
| --- | --- |
| Source → **Root Directory** | `/hr-system/hr-system` (the Laravel app's folder; without it Railway won't find Laravel) |
| Source → **Branch** | `main` |
| Deploy → **Healthcheck Path** | `/up` |
| Networking → **Generate Domain** | Click it to get `https://<name>.up.railway.app` |

**3. Variables.** Open the app service's **Variables** tab, choose **Raw Editor**, and paste the block below. Railway fills in each `${{...}}` part itself:

```dotenv
APP_NAME=Crewly
APP_ENV=production
APP_DEBUG=false
APP_KEY=<paste the output of: php artisan key:generate --show>
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
APP_TIMEZONE=Asia/Bangkok
DB_CONNECTION=mysql
DB_URL=${{MySQL.MYSQL_URL}}
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=${{RAILWAY_PUBLIC_DOMAIN}}
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=warning
MAIL_MAILER=log
AVATAR_DISK=public
DEMO_PASSWORD=<a private password for the demo accounts>
```

If the domain variable stays empty, generate the domain first (step 2), or paste the domain in place of `${{RAILWAY_PUBLIC_DOMAIN}}`.

**4. Persistent storage for profile photos.** Right-click the app service and choose **Attach Volume**. Set the mount path to `/app/storage/app/public`.

**5. Demo data (first deploy only).** Under Settings → Deploy → **Pre-Deploy Command**, enter:

```bash
php artisan migrate --force && php artisan db:seed --force
```

After the first successful deploy you can change this field to just `php artisan migrate --force`. Leaving `db:seed` in is harmless: the demo seeder only builds data on an **empty** database, so later deploys never undo changes made in the app (such as a renamed department).

**6. Deploy.** Click **Deploy**, or push to `main`. Watch **Deployments → View logs**. Then run the checks in [section 6](#6-after-the-first-deploy-checks) below.

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
| `MAIL_MAILER` | `log` until you set up a mail service (reset and verification links then appear in the logs), then `brevo`. See step 5 |
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

## 5. Real email (password reset and verification)

**Railway blocks outgoing SMTP** (ports 25, 465, 587 and 2525) on its Hobby and trial plans, so Gmail SMTP and other SMTP setups time out. The app sends mail through **Brevo's HTTPS API** instead, which works on every plan. Brevo's free plan allows 300 emails a day and doesn't need a domain of your own.

1. Create a free account at brevo.com.
2. **Senders, domains & dedicated IPs → Senders → Add a sender:** enter the address the emails should come from (for example your Gmail) and click the confirmation link Brevo emails you.
3. **SMTP & API → API keys → Generate a new API key.** Copy it; Brevo only shows it once.
4. In the host's variables, set:

   | Variable | Value |
   | --- | --- |
   | `MAIL_MAILER` | `brevo` |
   | `BREVO_API_KEY` | the key from step 3 (**private**, never commit it) |
   | `MAIL_FROM_ADDRESS` | the sender you verified in step 2, exactly |
   | `MAIL_FROM_NAME` | `Crewly` |

5. Redeploy. Then sign in, open **Profile → Send verification email**, and check the inbox. Also try **Forgot password** on the sign-in page.

Without your own domain the emails are not signed for your address (no DKIM), so the **first ones may land in spam**. Mark them "Not spam". To switch back to logging, set `MAIL_MAILER=log`.

---

## 6. After the first deploy: checks

1. `https://your-domain/up` shows a success page.
2. `https://your-domain/` shows the Crewly sign-in screen.
3. Sign in as `admin@test.com` with your `DEMO_PASSWORD`. The dashboard and charts should load.
4. Refresh the page. You should stay signed in, which shows the cookie session and HTTPS are working.
5. Upload a profile photo, redeploy, and check the photo is still there.
6. As HR, open All Leave and click **Export CSV**. A file should download.
7. In Postman, set the environment's `baseUrl` to `https://your-domain` and run *Auth → Get token as Admin*.

Then add the live URLs to the table at the top of `README.md`.

---

## 7. Common problems

| Symptom | Cause and fix |
| --- | --- |
| Sign-in works, but every request then gives 401, or a refresh signs you out | `SANCTUM_STATEFUL_DOMAINS` doesn't match the domain exactly, or `SESSION_SECURE_COOKIE=true` is set without HTTPS |
| Error 419 "session expired" right away | `APP_URL` doesn't match the real domain. Clear and rebuild the config cache after changing variables (`php artisan config:clear && php artisan config:cache`) |
| Pages load without styles, or the console shows 404s for `/build/...` | `npm run build` didn't run during the build step |
| Error 500 with no details | Check the host's logs (`LOG_CHANNEL=stderr`). The most common cause is a missing `APP_KEY` or wrong `DB_*` values |
| Profile photos disappear after a redeploy | There's no persistent storage. See step 4 |
| The email-verification link says "invalid" | `APP_URL` isn't the https address. The app already trusts the host's proxy, so this is almost always the setting |
| Changed variables have no effect | The config is cached. Redeploy, or run `php artisan config:cache` again |
