# Quality MS

An open-source quality management system (QMS) for manufacturers. It links the whole quality chain, so any lot can be traced from the supplier certificate it arrived with to the corrective action that fixed a problem:

```
supplier lot → material certificate (checked against spec limits) → inspection (with calibrated gauges) → NCR → CAPA (8D) → controlled document revision
```

## Features

- **Supplier material certificates**: EN 10204 2.1 / 2.2 / 3.1 / 3.2 mill test certificates, certificates of analysis (CoA) and certificates of conformance (CoC). Every result is checked against the material's specification limits, including size-dependent limits, before the certificate can be verified. Optional: read the lots and results from the PDF or scan with Claude, for a person to check before saving.
- **Lot traceability**: find any heat, batch or date code and the certificate behind it.
- **Calibration**: gauge register, calibration history with certificates, due and overdue status, daily reminder emails, and a suspect-inspection list when a gauge fails.
- **Inspection plans and inspections**: revisioned plans per part or material and stage, measured and OK / not-OK characteristics with sample sizes, readings taken only with gauges in calibration.
- **Non-conformance reports**: raised automatically from failed inspections, rejected certificates, failed calibrations and audit findings; disposition approval; printable report.
- **CAPA (8D)**: stage-gated through the eight disciplines, actions, effectiveness check, printable 8D report.
- **Document control (ISO 9001 §7.5)**: revisions, review and approval by someone other than the author, read-and-acknowledge lists, periodic review reminders.
- **Internal audits (ISO 9001 §9.2)**: planned audits, findings, nonconformities raise NCRs.
- **Dashboard**: what needs attention today, NCR Pareto by source, ISO 9001 evidence matrix with gaps.
- **Electronic signatures** (21 CFR Part 11 style) on approvals and decisions, and an **append-only audit trail** of every change.
- **English, Bahasa Melayu and 中文**, with a built-in user guide in each.

## Stack

Laravel 13 · Livewire 4 · Flux UI (free) · Tailwind CSS 4 · Fortify (login, 2FA, passkeys) · Spatie Permission · Pest · PHPStan (Larastan level 7) · MySQL (SQLite for tests).

## Getting started

Requirements: PHP 8.4, Composer, Node 22, MySQL 8 (or SQLite).

```bash
git clone https://github.com/pasupathy-manikam-jr/quality-ms.git
cd quality-ms
composer setup          # install, .env, key, migrate, npm install, build
php artisan db:seed     # demo data (metals and plastics)
composer dev            # app, queue, logs and Vite together
```

`.env.example` uses SQLite; for MySQL set the `DB_*` values.

Demo accounts (password `Zx123456`): `admin@example.com`, `quality-manager@example.com`, `inspector@example.com`, `auditor@example.com`, `viewer@example.com`. Change or remove them before going live.

## Configuration

| Variable | Default | What it does |
|---|---|---|
| `APP_TIMEZONE` | `UTC` | Dates such as "due today" and the time reminder emails go out. |
| `QMS_LOT_LABEL` | `Lot` | What a supplier lot is called on screen, e.g. `Heat number` or `Batch`. |
| `QMS_DUE_SOON_DAYS` | `14` | How many days ahead gauges show as due and documents as due for review. |
| `ANTHROPIC_API_KEY` | empty | Switches on reading certificate files with Claude. Leave empty to keep it off. |
| `ANTHROPIC_MODEL` | `claude-opus-5-5` | The Claude model used to read certificates. |
| `MAIL_*` | `log` | Mail transport for reminder emails. |

### Scheduled jobs

Reminder emails need Laravel's scheduler, run every minute by cron:

```
* * * * * php /path/to/quality-ms/artisan schedule:run >> /dev/null 2>&1
```

| Command | When | What |
|---|---|---|
| `qms:calibration-reminders` | daily 07:00 | Emails gauge owners their due and overdue gauges. |
| `qms:document-review-reminders` | daily 07:05 | Emails document owners their documents due for review. |

## Development

```bash
composer ci:check       # Pint, PHPStan and the Pest suite: exactly what CI runs
php artisan test --compact --filter=Certificate
```

- Roles and permissions are defined in code (`database/seeders/RolesSeeder.php`). After changing them run `php artisan db:seed --class="Database\Seeders\RolesSeeder"`.
- UI text goes through `__()`; translations live in `lang/ms.json`, `lang/zh.json` and `lang/{ms,zh}/*.php`. The user guide is `resources/guide/{en,ms,zh}.md`.
- Module pages are class-based Livewire components in `app/Livewire/<Module>/`, routed from `routes/modules/<module>.php`.

## Deployment

CI (`.github/workflows/tests.yml`) runs `composer ci:check` on every push. On pushes to `main`, `.github/workflows/deploy-assets.yml` builds the frontend and publishes a `deploy` branch (`main` plus compiled `public/build`), so servers never need Node.

On the server:

```bash
cd ~/quality-ms && bash scripts/deploy.sh   # pull deploy branch, composer install, migrate, sync roles, cache
```

To serve from a subfolder (e.g. `https://example.com/quality-ms`), build with `APP_PATH_PREFIX=quality-ms` and set `APP_URL`, `ASSET_URL`, `SESSION_PATH` and `SESSION_COOKIE` to match.

## Licence

To be decided before the repository is made public.
