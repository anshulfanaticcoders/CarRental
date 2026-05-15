# Queue Worker — Production Setup

## What runs on the queue

| Job | Triggered by | Why async |
|-----|--------------|-----------|
| `App\Jobs\TriggerProviderReservationJob` | `StripeBookingService::createBookingFromSession` after a successful payment for an external-provider booking | Provider API (greenmotion, surprice, locauto, …) can be slow or transiently down. Retries up to 5 times with 1m → 1h backoff so a flaky supplier never costs the customer a paid booking. |
| `App\Jobs\SendAwinConversion` | Same path, after commit | Awin affiliate ping — non-critical, must not block the response. |
| `App\Jobs\NewsletterCampaignSendJob`, `NewsletterRecipientProcessJob` | Admin send newsletter | Bulk email throttled. |
| Any `ShouldQueue` notification (e.g. `NewMessageNotification`, `MessageReminderNotification`, `BulkVehicleUploadNotification`) | Various controllers | Per-recipient SMTP latency must not block the request. |

## Required env (prod)

```
QUEUE_CONNECTION=database
```

The `jobs` and `failed_jobs` tables already exist (`php artisan queue:table` + `migrate`). Redis is an option later if volume grows; database is fine up to a few thousand jobs/day.

## Run a worker

```bash
php artisan queue:work database --queue=default --tries=5 --backoff=60,300,600,1800,3600 --sleep=3 --timeout=120
```

Flags explained:
- `--tries=5` matches `TriggerProviderReservationJob::$tries`. Other jobs override via `$tries` on the class.
- `--backoff=…` is overridden per-job by `$backoff`. Reservation job has its own progression; this is the fallback.
- `--timeout=120` kills a single job after 2 min. Reservation job has `$timeout = 120` set explicitly.
- `--sleep=3` polls every 3 s when idle (cheap).

## Supervisor recipe (`/etc/supervisor/conf.d/carrental-worker.conf`)

```ini
[program:carrental-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/CarRental/artisan queue:work database --queue=default --tries=5 --sleep=3 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/carrental-worker.log
stopwaitsecs=3600
```

`numprocs=2` is enough for current volume (one worker handles maybe 1k jobs/hour). Bump to 4 when traffic grows.

`stopwaitsecs=3600` lets in-flight jobs finish during deploys instead of being killed.

Reload after install:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start carrental-worker:*
```

## Deploy hook

On every deploy, restart workers so they pick up new code:

```bash
php artisan queue:restart
```

Supervisor will re-spawn them automatically.

## Monitoring

Failed jobs land in `failed_jobs`. Inspect:

```bash
php artisan queue:failed
```

Retry all:

```bash
php artisan queue:retry all
```

Retry one:

```bash
php artisan queue:retry <uuid>
```

Watch worker health from supervisor:

```bash
sudo supervisorctl status
```

If `carrental-worker:carrental-worker_00` is `FATAL` or repeatedly restarting, the worker is crashing — check `/var/log/supervisor/carrental-worker.log`.

## Scheduler (separate from worker — both must run)

`schedule:run` ticks the scheduler in `app/Console/Kernel.php`. Cron entry:

```cron
* * * * * cd /var/www/CarRental && php artisan schedule:run >> /dev/null 2>&1
```

Without this, `SendPendingBookingReminders` (twiceDaily), `RefreshCurrencyRates`, sitemap generation, and `SendChatMessageReminders` never run.
