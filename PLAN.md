# Quality MS — build plan

An open-source quality management system (QMS) for manufacturers in any industry. It covers inspection plans, supplier material certificates, non-conformance reports (NCRs), CAPA, calibration and ISO 9001 document control.

The supplier material certificate module covers:

- **Mill test certificates (MTCs)** for metals, to EN 10204 types 2.1, 2.2, 3.1 and 3.2.
- **Certificates of analysis (CoAs)** for chemicals, resins and food ingredients.
- **Certificates of conformance (CoCs)** for bought-in components.

Industry-specific wording is only a label: a metals user sees "heat number" where everyone else sees "lot" (a single `lot_label` setting).

**The differentiator:** it traces one chain end to end:

    supplier lot → material certificate (checked against spec limits) → inspection (with gauges that are in calibration) → NCR → CAPA → controlled document revision

No open-source tool links all of these. Certificate checking is the clearest gap: every MTC tool found is commercial (GoSmarter, PathNovo), and CoA handling only exists inside lab systems such as Senaite.

## What we borrow, and from where

| Source | Idea we take | What we leave out |
|---|---|---|
| OpenQMS (C-realize) | Approval hierarchy on documents and changes; an append-only audit log on every quality record | Life-science features (eTMF, batch records) |
| OCA `management-system` (Odoo) | A failed inspection auto-raises an NCR linked to that inspection; NCR → actions as separate records; NCR stages draft → analysis → pending → open → done | The Odoo runtime and the spread across many add-ons |
| odoo-qms-iso9001 | Corrective actions in 8D form (D1–D8); customer complaints feed NCRs | — |
| ERPNext Quality | Inspection templates: a parameter list with unit, min, max (or an accepted value), and pass/fail computed per reading | The ERP coupling |
| GaugeBase / Kalibro | Gauge register, due-date reminders, calibration certificate attached to each calibration event | Metrology calculations (uncertainty budgets) — later |
| FlinkISO | Organise by ISO 9001 clause, so each record shows which clause it evidences | Its non-open licence model |
| Commercial MTC tools | Per-lot check of certified results against spec limits; certificate type recorded per cert (EN 10204 types, CoA, CoC) | AI PDF extraction (phase 5, optional) |

## Stack (installed)

- **Laravel 13.34** (PHP ^8.4), **Livewire 4.4 + Flux 2 (free) + Blaze**, Tailwind 4, from the Laravel Livewire starter kit (main branch). Fortify handles login, 2FA and passkeys.
- Spatie permission 8.3, Boost 2.10, Pint, Larastan (level 7), PHPUnit 12, vite-plus.
- `barryvdh/laravel-dompdf` for NCR/CAPA/8D PDFs, added in phase 4.
- MySQL locally (MAMP, DB `quality_ms`) and in production; in-memory SQLite for tests.
- Rules from the other Sites projects are in `CLAUDE.md`. The backend helpers are ported from hrms (TableQuery, Csv, HasCreator, StoresUploads, Money only if needed). The React frontend rules were translated to Livewire/Flux equivalents.
- Staging: `~/quality-ms` → `https://ui.staging.oriclabdev.com/quality-ms`, DB `stagingoriclabde_qms`.

## Shared foundations (phase 0)

- **Users, roles and permissions.** Seeded by `RolesSeeder`, reused by tests via `userWithRole()`. Roles: `admin`, `quality-manager`, `inspector`, `auditor` (read-only), `viewer`. Permissions use `manage-x`, `create-x`, `edit-x`, `delete-x` and `approve-x` per module.
- **Numbering.** One `sequences` table hands out `NCR-2026-0001`, `CAPA-2026-0001`, and so on, locked with `lockForUpdate` per prefix and year.
- **Audit trail.** An `audit_logs` table (`user_id`, `auditable` morph, `event`, `before`/`after` JSON, `ip`, timestamp), written by one model trait. It is never updated or deleted. This is also the base for 21 CFR Part 11 later.
- **Files.** Each record holds at most one file (material certificate PDF, calibration cert, drawing, document revision), so use the `StoresUploads` concern (`file_*` columns on the private disk, served through an authorised route) plus a `file_sha256` column for integrity. No attachments table.
- **ISO clause tags.** A seeded `iso_clauses` table (ISO 9001:2015 §4–§10). Documents are tagged with clauses; module records count towards fixed clauses (gauges §7.1.5, certificates §8.4, inspections §8.6, NCRs §8.7, CAPAs §10.2).
- **Status changes.** Each workflow model has a `status` enum and an `ALLOWED` transitions map, all going through one `transitionTo()` method that checks the move, writes the audit log and stamps who/when. No state-machine package.

## Modules

### 1. Master data
- `suppliers` (name, code, approved flag, approval date, rating)
- `customers`
- `materials`: a material and the spec it is bought to, for example `EN 10025 S355JR` steel, `PA66-GF30` resin, or a food-grade ingredient.
- `material_limits`: one row per property, with `property`, `min`, `max` and `unit`.
  - Steel properties are C, Mn, yield and UTS; resin properties are melt flow and moisture; ingredient properties are pH, assay and microbial count.
  - An optional size range (`size_from`, `size_to`) handles limits that depend on size, such as steel thickness.
- `parts` (part no, revision, drawing file, material_id)

### 2. Supplier material certificates (headline feature)
- `certificates`:
  - cert no, supplier, issue date, PO no, PDF file
  - type: `en10204-2.1 | en10204-2.2 | en10204-3.1 | en10204-3.2 | coa | coc`
  - status: `received → verified | rejected`
- `lots`: lot no (a heat number, batch number or date code), certificate_id, material_id, size, quantity, expiry date (optional, for shelf-life materials).
- `lot_results`: lot_id, property, value, unit.
- **Verification:**
  - Each result is checked against the `material_limits` row for its property (and its size range, where one is set). The outcome is pass, fail or "no limit defined".
  - A cert can only be verified when every result passes.
  - A failure blocks verification and offers to raise an NCR with the lot pre-filled.
- **Rules:**
  - Types that report test results (EN 10204 2.2/3.1/3.2, CoA) need at least one result.
  - Declaration-only types (EN 10204 2.1, CoC) need none.
  - A type 3.2 cert needs a third-party inspector name.
  - An expired lot cannot be used in an inspection.
- **Traceability lookup:** search a lot number to see its cert, the inspections that used it, NCRs and CAPAs.

### 3. Calibration
- `gauges`: id/tag no, description, type, range, resolution, location, owner, interval (days), status `active | due | overdue | out-of-service | retired`.
- `calibrations`: gauge_id, performed_on, performed_by (internal/lab name), result `pass | adjusted | fail`, as-found and as-left notes, certificate file, next_due_on (computed).
- A daily scheduled command recomputes `due` (≤ 14 days, configurable in settings) and `overdue`, and emails gauge owners.
- **Rule:** when a calibration fails, list every inspection that used that gauge since its last passing calibration (the suspect-product report) and offer to raise an NCR.

### 4. Inspection plans and records
- `inspection_plans`: part_id (or material_id), revision, stage `receiving | in-process | final`, status `draft → approved → obsolete`.
- `inspection_plan_items`: characteristic, method, gauge type required, nominal, lower tolerance, upper tolerance (or accepted value for visual checks), unit, sample size, critical flag.
- `inspections`: plan_id (pinned to its revision), lot_id, quantity, inspector, date, status `in-progress → passed | failed`.
- `inspection_readings`: plan_item_id, gauge_id, value or OK/NOK, result (computed).
- **Rules:**
  - Saving a reading with a gauge that is overdue or out of service is refused.
  - An inspection fails if any critical item fails.
  - A failed inspection auto-creates a draft NCR linked to it (the OCA pattern).

### 5. Non-conformance reports
- `ncrs`: number, source `inspection | certificate | calibration | customer-complaint | supplier | internal-audit | other`, source morph link, part/lot, quantity affected, description, severity `minor | major | critical`.
- **Disposition:** `use-as-is | rework | repair | scrap | return-to-supplier`, with approval by a quality manager.
- **Status:** `draft → open → disposition-approved → closed`. An NCR cannot close while a linked CAPA is still open.
- Customer complaints are simply NCRs with `source = customer-complaint` plus customer fields. No separate module.

### 6. CAPA (8D)
- `capas`: number, type `corrective | preventive`, ncr links (many-to-many, so one CAPA can cover a recurring problem), owner, due date.
- The eight disciplines (D1 team, D2 problem, D3 containment, D4 root cause with 5-Why/fishbone text, D5 chosen actions, D6 implementation, D7 prevent recurrence, D8 closure) are text fields on the CAPA, filled stage by stage.
- `capa_actions`: description, owner, due, done_at.
- **Effectiveness check:** a date plus a verified flag. **Status:** `open → investigating → implementing → verifying → closed`. It can only close once the effectiveness check is verified.
- D7 can open a document change request (module 7).

### 7. Document control (ISO 9001 §7.5)
- `documents`: doc no, title, type `policy | procedure | work-instruction | form | record`, owner, ISO clause links, review interval.
- `document_revisions`: rev letter, file, change summary, status `draft → in-review → approved → effective → superseded`, approved_by/at.
- Only one revision can be effective at a time; approving a new one supersedes the old.
- `document_acknowledgements`: users assigned to read an effective revision record when they acknowledge it (a light version of OpenQMS training records).
- Periodic review reminders run from the same daily command as calibration.

### 8. Dashboard
- Counts: open NCRs, overdue CAPAs, gauges due/overdue, certificates awaiting verification, documents due for review.
- An ISO clause matrix showing evidence counts per clause.
- One Pareto chart of NCRs by cause/source over the last 90 days.

## Phases (each one shippable with CI green)

| Phase | Scope | Done when |
|---|---|---|
| 0 ✅ | Roles + permissions (`RolesSeeder`), `Sequence` numbering, append-only audit trail (`Auditable`), ISO clause list, soft-deleted users, Users admin page (the reference listing: `WithTable` + Flux table/modals), class-based pages so PHPStan covers them, deploy workflow + `scripts/deploy.sh` + Vite subfolder base. **Deferred to the first module that needs them:** `transitionTo()`, file uploads, clause links, `countBy()` status tabs, CSV. **Waiting on git:** staging setup | Done locally: `composer ci:check` green (52 tests); admin login and `/users` work on MAMP |
| 1 ✅ | Suppliers (approval), materials + specification limits (size ranges, overlap check), certificates (EN 10204 2.1–3.2, CoA, CoC) with lots and results, verification checklist, verify/reject with audit trail, private hashed file storage, lot traceability lookup, `lot_label` setting, metals + plastics demo data. **Moved:** "raise NCR from a failed certificate" → phase 4 (needs the NCR module); parts → phase 3; customers → phase 4 | The S355 MTC (C 0.26 > 0.24) and the resin CoA (moisture 0.35 > 0.2) are both blocked from verification; 84 tests green |
| 2 ✅ | Gauge register; state (calibrated/due/overdue) derived from the due date, only the lifecycle (active/out-of-service/retired) is stored; calibrations are append-only (pass/adjusted sets the next due date, fail takes the gauge out of service, back-dating refused); private calibration certificates; daily `qms:calibration-reminders` at 07:00 emails each owner. **Moved:** suspect-inspection report → phase 3 (needs inspections) | Overdue/never-calibrated gauges are flagged and unusable; failed calibration takes the gauge out of service; 95 tests green |
| 3 ✅ | Parts; inspection plans (draft → approved → obsolete, revisions copy characteristics, approved plans locked); characteristics (measured min/max/nominal or OK/not OK, sample size, critical flag); inspections numbered INS-YYYY-NNNN against an approved plan; receiving inspections need a verified, unexpired lot of the plan's material; readings refused with a gauge that is overdue/never calibrated/out of service; any failed reading fails the inspection; suspect-inspection list on a gauge that failed calibration. **Changed:** any failed reading (not only critical) fails the inspection — critical sets NCR severity in phase 4. **Moved:** auto-NCR on failure → phase 4 | Unusable gauge refused; out-of-spec reading fails the inspection; seeded bracket inspection shows as suspect on HRD-002; 114 tests green (Pest) |
| 4 ✅ | **NCRs:** raised automatically as drafts by a failed inspection (major if a critical characteristic failed), a rejected certificate and a failed calibration; hand-entered for complaints, supplier issues, audits; draft → open → disposition approved → closed (or cancelled with a reason); cannot close while a linked CAPA is open. **CAPA (8D):** open → investigating → implementing → verifying → closed, each step gated by its disciplines, all actions done and a verified effectiveness check; several NCRs per CAPA; D7 starts a document revision. **Documents:** revisions A, B, … draft → in review → effective → superseded; no self-approval; readers and acknowledgements; periodic review with reminders. **Dashboard:** permission-aware tiles, NCR Pareto (CSS bars, no chart library), ISO 9001 evidence matrix. **Simplified:** approve = effective (no separate approved state); customers are a text field; clause tags only on documents, module records map to fixed clauses | Failure → NCR → CAPA → document revision chain covered by tests; 142 tests green |
| 5 (partly ✅) | **Done:** internal audits (§9.2: plan → in progress → completed, clauses in scope, append-only findings, nonconformities raise NCRs); supplier scorecard (certificate acceptance %, NCRs in 12 months); printable NCR and 8D reports (browser print-to-PDF, no PDF library). **Open, needs a decision:** e-signatures with password re-entry on approvals (21 CFR Part 11), AI certificate PDF extraction (API key + dependency), ms/zh translations | 149 tests green |

## Demo data
Seeders ship two industry profiles, so the generic model is exercised from day one:

- metals: steel grades, MTCs and heat numbers
- plastics: resins, CoAs and batch numbers

Each profile is a set of `database/demo/*.json` files.

## Out of scope for v1
SPC charts, gage R&R (MSA), PPAP/APQP, multi-tenancy, ERP integrations. Add them when a real user needs them.

## Licence
AGPL-3.0 (the same as OpenQMS) keeps hosted forks open. Decide before the first public push.
