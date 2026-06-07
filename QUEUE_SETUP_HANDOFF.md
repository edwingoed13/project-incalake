# P1 Queue Worker Setup — cPanel Handoff

The performance plan needs background jobs (payment confirmation, emails,
Google Calendar sync) running OUT of the HTTP request path. The Laravel
side is config-ready after the latest deploy; this doc covers the parts
only you can do on the cPanel side.

## Prerequisites already shipped

| Change | Detail |
|---|---|
| `config/queue.php` default | Already `database` (no edit needed) |
| Migration `2026_06_07_180100_create_queue_tables.php` | Creates `jobs`, `job_batches`, `failed_jobs` tables. Runs via `migrate.php?key=<KEY>` |
| Migration `2026_06_07_180000_add_performance_indexes.php` | Top-4 DB indexes (languages.code, tour_translations.language_id, bookings composite, tour_prices composite). Idempotent |

## Step 1 — Apply the migrations (`2 min`)

After backend FTP deploy finishes, hit:
```
https://api.incalake.com/migrate.php?key=<DEPLOY_HOOK_KEY>
```

Should return JSON with both migrations marked `DONE`. Verify with:
```
https://api.incalake.com/migrate.php?key=<DEPLOY_HOOK_KEY>&action=status
```

## Step 2 — Flip QUEUE_CONNECTION on prod `.env` (`2 min`)

In cPanel → File Manager → `incalake-api/.env`:

```diff
- QUEUE_CONNECTION=sync
+ QUEUE_CONNECTION=database
```

Then hit `purge-cache.php?key=<DEPLOY_HOOK_KEY>` so Laravel re-reads the
config.

**If the line doesn't exist**, add it. Default before this change is `sync`
either way.

## Step 3 — Create the cron-based queue worker (`5 min`)

cPanel → Cron Jobs → Add New Cron Job:

| Field | Value |
|---|---|
| Common Setting | "Every minute" (`* * * * *`) |
| Command | `cd /home/inc0910d/incalake-api && /usr/local/bin/php artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=55 --stop-when-empty >> storage/logs/queue.log 2>&1` |

What each flag does:

- `--max-time=55` — worker quits after 55 seconds, before the next cron
  fires. No overlap, no memory bloat from long-running PHP.
- `--stop-when-empty` — if there are no jobs, worker exits immediately,
  doesn't waste CPU polling.
- `--tries=3` — failed jobs retry twice (3 attempts total) before landing
  in `failed_jobs`. Adjust per job class with `public $tries`.
- `--sleep=3` — sleep 3s when there are no jobs (before exiting via
  `--stop-when-empty`).
- `>> storage/logs/queue.log 2>&1` — keep worker output in a separate file
  so the main app log stays readable.

**Adjust the PHP binary path** if cPanel uses something other than
`/usr/local/bin/php`. Check the path used by the existing migrate.php
cron / hook:
```bash
which php
# or: ls -la /usr/local/bin/php*
```

## Step 4 — Verify the worker is alive (`2 min`)

```sql
-- in phpMyAdmin
SELECT COUNT(*) AS pending FROM jobs;
SELECT COUNT(*) AS failed FROM failed_jobs;
```

Both should be `0` initially. After the cron fires once (≤ 1 min), it
should still be `0` — the worker has nothing to process yet but the table
existed and connected fine. Check `storage/logs/queue.log` for any
"connection refused" / "table not found" errors.

To **smoke test** end-to-end (run from a terminal that can SSH to cPanel,
or skip and wait for the first real job):

```bash
php artisan tinker
>>> Mail::to('your@inbox')->later(now(), new \App\Mail\BookingTravelersCompletedMail($booking));
```

The mail should arrive ~1 minute later (next cron tick).

## Step 5 — Failed job monitoring (`later`)

In Phase 2 we'll add a cron that emails you if `failed_jobs` grows above
a threshold. For now, peek every couple of days:

```sql
SELECT id, queue, failed_at, SUBSTRING(exception, 1, 300) AS reason
FROM failed_jobs
ORDER BY failed_at DESC LIMIT 20;
```

To re-try a single failed job from cPanel terminal:
```bash
php artisan queue:retry <uuid>
# or all:
php artisan queue:retry all
```

---

## What this unlocks

Once the worker is running, the next backend deploy can move payment
confirmation, Google Calendar event creation, and confirmation email
sends to background jobs. That's the change that drops Culqi/PayPal
confirmation latency from 8-10s → <500ms perceived. Without the worker,
those `dispatch()` calls fall through to sync execution and we gain
nothing.

## Rollback

If anything misbehaves:

1. **Disable the cron** (cPanel → Cron Jobs → toggle off). Jobs stop
   processing; they queue up in the `jobs` table waiting.
2. **Flip `.env` back** to `QUEUE_CONNECTION=sync` + purge cache. All
   future dispatches run inline again.
3. The two new migrations are reversible (`migrate.php?key=...&action=rollback`
   if you add that action), but leaving the empty tables in place
   doesn't cost anything.
