# User guide

Quality MS keeps your quality records in one place: supplier certificates, inspections, calibration, non-conformances, corrective actions, controlled documents and internal audits. Each part links to the next, so you can trace any lot from the certificate it arrived with to the corrective action that fixed a problem.

[TOC]

## Getting started

### Signing in and your account

Sign in with the email and password your administrator gave you. Under **Settings** (bottom of the sidebar, in your user menu) you can change your name, email and password, and turn on two-factor authentication or a passkey.

### Language

Choose **English**, **Bahasa Melayu** or **中文** from your user menu. Your choice is saved on your account and follows you to any device.

### Finding your way around

The sidebar groups the work:

- **Incoming**: certificates, lot lookup, suppliers and materials.
- **Quality**: inspections, inspection plans, parts and gauges.
- **Improvement**: non-conformances, CAPA, internal audits and documents.
- **Administration**: users (administrators only).

You only see the pages your role allows.

### Lists

Every list works the same way. Type in the search box to filter, use the drop-downs and the status tabs to narrow it down, click a column heading to sort, and choose how many rows to show. The address bar keeps your search and filters, so you can bookmark or share a filtered list.

### Required fields

A red **\*** after a field name means the field must be filled in. When something is missing or wrong, the message appears under the field when you save.

## Roles

| Role | What they do |
|---|---|
| Admin | Everything, including managing users and roles. |
| Quality manager | Approves and decides: verifies certificates, approves plans and documents, records calibrations, approves NCR dispositions, closes NCRs and CAPAs. |
| Inspector | Records the work: certificates and their results, inspections, NCRs and CAPA actions. |
| Auditor | Reads everything and runs internal audits. |
| Viewer | Reads everything. |

## Dashboard

The dashboard shows what needs attention today: open NCRs, overdue CAPAs, gauges due or overdue for calibration, certificates waiting for verification, inspections in progress, documents due for review, and documents you have been asked to read. Click a number to open that list.

Below the numbers:

- **Non-conformances by source** (last 90 days), largest first. Fixing the top one or two sources usually removes most problems.
- **ISO 9001 evidence** lists every clause with the documents and records that cover it. A clause marked **Gap** has neither; look at it before an audit.

## Incoming material

### Suppliers

Add each supplier with a code and name. Tick **Approved supplier** and enter the approval date once the supplier has been approved. Certificates from a supplier that is not approved cannot be verified.

The list also shows how each supplier is performing: the share of their certificates that were accepted, and how many NCRs were raised against them in the last 12 months.

### Materials and specification limits

A material is something you buy to a specification, for example *S355JR steel to EN 10025-2* or *PA66-GF30 resin*. Open a material to add its **specification limits**: one row per property (C, Yield, Moisture…) with a minimum, a maximum or both. Values exactly on a limit pass.

If limits depend on size (steel yield strength by plate thickness, for example), fill in **Size dimension** on the material, then give each limit a size range. Ranges for the same property may not overlap.

### Certificates

When material arrives, add its certificate:

1. Go to **Certificates** and choose **Add certificate**. Pick the supplier, enter the certificate number, type and issue date, and attach the PDF or scan.
2. On the certificate page, add each **lot** (heat number, batch or date code) with its material, size and quantity.
3. Choose **Enter results** for each lot and copy the values from the certificate. Leave a box empty if the value is not reported.

The page checks every result against the material's limits and lists anything that stops verification: a value outside its limit, a missing result, a supplier that is not approved, or a 3.2 certificate without its third-party inspector.

- **EN 10204 2.2, 3.1, 3.2 and certificates of analysis** report test results, so every limited property needs a result.
- **EN 10204 2.1 and certificates of conformance** only declare conformity; they need their lots but no results.

A quality manager then chooses **Verify** (when the checklist is clear) or **Reject** with a reason. Both ask for an electronic signature. A rejected certificate opens an NCR automatically. A decided certificate can no longer be changed.

### Reading a certificate file automatically

If your administrator has switched it on, **Read from file** reads the attached PDF or scan and fills in the lots and results for you. Nothing is saved until you have checked it:

1. Choose **Read from file** and wait a few seconds.
2. Compare every value with the certificate and correct anything wrong. Read the notes; they point out anything that was unclear.
3. Choose the material for any new lots, then **Apply results**.

Always check the values against the certificate before you verify it.

### Lot lookup

Type a heat number, batch number, certificate or supplier to find a lot and the certificate it arrived with.

## Calibration

### Gauges

Every measuring instrument is a gauge with a code, a calibration interval and, ideally, an owner. Its status is worked out from its due date:

| Status | Meaning | Can it be used? |
|---|---|---|
| Calibrated | Due date is more than 14 days away | Yes |
| Due | Due within 14 days | Yes, until the due date |
| Overdue | Past its due date, or never calibrated | No |
| Out of service | Failed calibration or taken out of use | No |
| Retired | No longer used | No |

Gauge owners get a reminder email each morning when their gauges are due or overdue.

### Recording a calibration

Open the gauge and choose **Record calibration**. Enter the date, who calibrated it and the result, and attach the calibration certificate.

- **Pass** sets the next due date and returns the gauge to service.
- **Pass after adjustment** does the same; describe what was found and how it was left.
- **Fail** takes the gauge out of service and opens an NCR.

Calibration records cannot be edited. To correct one, record a new calibration.

### When a gauge fails

The gauge page lists the **suspect inspections**: every inspection that used the gauge since its last good calibration. Review them; their results may be wrong. The list clears once the gauge passes a calibration again.

## Inspection

### Parts

Add the parts you make with their part number, drawing revision and, if known, the material they are made from.

### Inspection plans

A plan lists what to check for a part or purchased material at one stage (receiving, in-process or final).

1. Choose **Add plan**, pick the part or material, the stage and a title.
2. Add **characteristics**: a measured value with its limits (for example 9.95 to 10.05 mm), or an OK / not OK check. Set the number of samples, and mark critical characteristics.
3. A quality manager approves the plan with an electronic signature.

An approved plan is locked so past inspections keep the exact plan they used. To change it, choose **New revision**; approving the new revision makes the old one obsolete.

### Doing an inspection

1. Choose **Start inspection**, pick an approved plan and, for receiving inspection, the lot (only lots from verified, unexpired certificates are offered).
2. Enter each reading. Measured values need the gauge you used; only gauges in calibration are offered.
3. **Save readings** as you go. When everything is entered, choose **Complete inspection** and sign.

If any reading is out of specification, the inspection fails and an NCR opens automatically. A failed critical characteristic makes the NCR major.

## Non-conformances (NCRs)

NCRs open automatically as drafts when an inspection fails, a certificate is rejected, a calibration fails, or an internal audit finds a nonconformity. Add customer complaints, supplier problems and other findings with **Add NCR**.

1. **Draft**: check the details and describe the problem, then choose **Open NCR**. A draft raised in error can be cancelled with a reason.
2. **Open**: decide what happens to the affected material (use as is, rework, repair, scrap or return to supplier) and save the disposition. "Use as is" needs a justification.
3. A quality manager **approves the disposition** with an electronic signature.
4. A quality manager **closes** the NCR. It cannot close while a linked CAPA is still open.

If the cause needs fixing, not just this material, choose **Start CAPA**. Use **Print** for a printable report; your browser can save it as a PDF.

## Corrective action (CAPA, 8D)

A CAPA works through the eight disciplines:

| Stage | Before moving on, fill in |
|---|---|
| Open | D1 Team, D2 Problem description |
| Investigating | D3 Containment, D4 Root cause, D5 Chosen actions |
| Implementing | D6 Implementation, and tick every action done |
| Verifying | The effectiveness check, D7 Prevent recurrence, D8 Closure |
| Closed | Nothing more; the CAPA is locked |

- Add **actions** with an owner and due date, and tick them off when done.
- Link further NCRs if the same problem keeps coming back.
- In D7, **change a controlled document** starts a new draft revision of the procedure that must change, linked back to the CAPA.
- A quality manager verifies effectiveness ("the problem has not come back") and closes the CAPA, both with an electronic signature.

**8D report** gives a printable version.

## Documents

Controlled documents (policies, procedures, work instructions, forms) live under **Documents**.

1. **Add document** creates it with draft revision A. Upload the file (PDF, Word, Excel or a scan) and describe what changed.
2. **Send for review**.
3. Someone other than the author **approves** it with an electronic signature. It becomes effective at once and replaces the previous revision.
4. Choose who must read it. They see it in **My reading list** and confirm once they have read it.

Each document has a review interval. When a review is due, its owner gets a reminder; choose **Confirm review** if nothing needs to change, or **New revision** if it does.

Tag documents with the ISO 9001 clauses they cover; the dashboard uses the tags.

## Internal audits

1. **Plan audit**: title, scope, lead auditor, date and the ISO clauses in scope.
2. **Start audit** on the day.
3. Record **findings**: major or minor nonconformities, observations and opportunities for improvement. Each nonconformity opens an NCR straight away. Findings cannot be changed afterwards.
4. Write the summary and **Complete audit**.

## Electronic signatures

Approvals, verifications, rejections, closures and inspection sign-off ask for your password again. Your name, the time and what you signed are saved with the record and shown on it, and cannot be changed or deleted. Never share your password; a signature in your name means you made that decision.

## Audit trail

Every change to a quality record is logged with who made it, when, and the values before and after. Nobody can edit or delete the log. Records that matter for an audit (calibrations, findings, signatures) cannot be deleted either; they are corrected by adding a new record.

## Questions

**Why can't I see a page or a button?** Your role does not include it. Ask your administrator.

**Why is Verify greyed out?** The checklist on the certificate page says what is still missing.

**Why can't I choose a gauge?** Only gauges in calibration are offered. Check the gauge's status under **Gauges**.

**Why can't I change an approved plan or a verified certificate?** Approved and decided records are locked so the history stays trustworthy. Create a new revision, or raise an NCR.
