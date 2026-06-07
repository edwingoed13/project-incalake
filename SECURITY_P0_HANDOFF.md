# P0 Security Handoff — Manual Steps Required

Code changes are already merged (commit included rate limits, log masking,
console-strip, leaked-file removal). **The items below need access I do not
have** — production `.env`, your hosting DB, and external dashboards (Culqi,
PayPal, Google). Run them in order; each section says how to verify.

## 1. Disable `APP_DEBUG` in production (`5 min`)

Stack traces and SQL queries are leaking on 500 responses today. Fix:

1. SSH or open cPanel → File Manager → `incalake-api/.env`
2. Change `APP_DEBUG=true` → `APP_DEBUG=false`
3. Hit `https://api.incalake.com/purge-cache.php?key=<DEPLOY_HOOK_KEY>` to
   clear compiled config.

**Verify**: trigger any 500 (e.g. `curl https://api.incalake.com/api/tours/9999999`)
— response body should be a generic error, NOT a stack trace.

## 2. Rotate the admin password (`5 min`)

`admin@incalake.com` was published with password `password` in the markdown
we just removed. Anyone who cloned the repo (or read it on GitHub) still
has that password.

```sql
-- run in phpMyAdmin / cPanel MySQL
UPDATE users
SET password = '<bcrypt hash>'
WHERE email IN ('admin@incalake.com', 'staff@incalake.com');
```

Generate the bcrypt hash with `php artisan tinker` locally:
```php
>>> Hash::make('your-new-strong-password')
```
Or use any bcrypt generator (cost 10+). **Do NOT reuse `password` /
`incalake123` / similar.**

**Verify**: try logging into the admin with the old password — should fail.

## 3. Rotate every API key (`30 min`)

The `.env` on prod cPanel was never in git, BUT we should rotate as a
hygiene step since this is the first security audit and we don't know what
got copy-pasted into Slack / chats / screenshots over the project's life.

Rotate in this order, then redeploy each new key to `.env` on cPanel:

| Service | Where to rotate | Variable(s) to update |
|---|---|---|
| **Culqi** | https://integraciones.culqi.com → API Keys → "Rotar" | `CULQI_PUBLIC_KEY`, `CULQI_SECRET_KEY` |
| **PayPal** | https://developer.paypal.com → My Apps → Live → regen secret | `PAYPAL_CLIENT_ID`, `PAYPAL_SECRET` |
| **Gmail SMTP** | Google Account → App passwords → revoke + regen | `MAIL_PASSWORD` |
| **Google Maps** | https://console.cloud.google.com → APIs → Credentials → restrict + regen | `GOOGLE_MAPS_API_KEY` (frontend uses NUXT_PUBLIC_*) |
| **DEPLOY_HOOK_KEY** | generate any 32+ char random string | `DEPLOY_HOOK_KEY` |

After each, edit `.env` on cPanel + hit `purge-cache.php?key=<NEW_KEY>` so
config cache picks up.

**Verify Culqi/PayPal**: place a 1-PEN test booking through the public site
end-to-end. If payment succeeds and the confirmation email arrives, both
new credentials are wired correctly.

## 4. (Optional) Purge the leaked file from git history

`CREDENCIALES_PRUEBA.md` is gone from `HEAD` but still readable in old
commits via GitHub UI. To fully remove it:

```bash
# Requires git-filter-repo installed (pip install git-filter-repo)
git filter-repo --invert-paths --path CREDENCIALES_PRUEBA.md
git push origin master --force
```

**Destructive**: rewrites history, requires force-push, breaks anyone with
a local clone (they'll need to `git clone` fresh). Only do this if you're
sure no collaborator has a half-finished branch off the old history.

Alternative: rotate the admin password (step 2) and consider the leak
mitigated even if the file stays in history — the password the file
documents is no longer valid.

## 5. (Recommended) Branch protection on `master`

GitHub → Settings → Branches → Add rule for `master`:
- Require pull request reviews before merging
- Require status checks to pass (we don't have CI yet — add `composer install` + `php artisan test` job next)
- Restrict who can push directly

Today a single bad push to master deploys backend + frontend to prod with
zero gating.

---

## What I already did (no action needed)

| Change | File |
|---|---|
| Deleted leaked credentials doc | `CREDENCIALES_PRUEBA.md` (gone) |
| Deleted stale backup files of `BookingController` | `.bak` + `.backup` (gone) |
| Added `throttle:3,1` to `/auth/register` | `routes/api.php` |
| Added `throttle:5,1` to `/auth/login` | `routes/api.php` |
| Tightened `/bookings` writes to `throttle:20,1` | `routes/api.php` |
| Masked `Booking request received` log (was leaking PII + headers) | `BookingController.php` |
| `esbuild.drop` in prod build strips `console.*` + `debugger` | `frontend/nuxt.config.ts`, `admin/nuxt.config.ts` |
| Fixed admin's duplicate `vite:` block (server config was being silently ignored) | `admin/nuxt.config.ts` |
