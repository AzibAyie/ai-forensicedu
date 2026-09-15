# AI-ForensicEdu — Setup Guide

Laravel 12 digital-forensics teaching platform. Students investigate simulated
breaches from real evidence, then file a report a lecturer grades.

---

## Requirements

- PHP 8.2+
- Composer 2
- MySQL 8 (or MariaDB 10.6+)
- VS Code (recommended)

---

## Install

```bash
# 1. Dependencies
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Create the database
mysql -u root -p -e "CREATE DATABASE ai_forensicedu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Point .env at it — edit DB_USERNAME / DB_PASSWORD
#    and add your AI settings (see "AI setup" below):
#    GROQ_PROXY_URL=...
#    GROQ_PROXY_SECRET=...
#    GROQ_MODEL=openai/gpt-oss-120b

# 5. Tables + demo data
php artisan migrate --seed

# 6. Run
php artisan serve
```

Open **http://localhost:8000**

---

## Accounts

| Role | Email | Password |
|---|---|---|
| Lecturer | lecturer@forensicedu.test | password |
| Lecturer | lecturer2@forensicedu.test | password |
| Student | student@forensicedu.test | password |
| Students 2–5 | student2…5@forensicedu.test | password |

All five students are assigned to `lecturer@forensicedu.test`.
Case 2 ("The Phantom Raise") is password-locked: **forensic2024**

---

## Feature map

### Student
| Page | Route | What it does |
|---|---|---|
| Case Board | `/student/dashboard` | Available cases with level, duration, marks. Active case banner with progress. One case at a time — a new case cannot open until the current report is filed. |
| Case Scenario | `/student/case/{id}` | Scenario, evidence panels (audit logs, DB records, network logs, system info, incident timeline), guided questions with autosave, live activity log. |
| Case Report | `/student/case/{id}/report` | Report sections, question-sheet download, answer-PDF upload, draft autosave. |
| My Record | `/student/record` | Score history chart, incident-type coverage, milestones, activity trail. |
| Profile | `/student/profile` | Editable details. |

### Lecturer
| Page | Route | What it does |
|---|---|---|
| Case Registry | `/lecturer/dashboard` | All cases with enrolment/submission counts. Publish, lock, edit, delete. Shows session window state. |
| Build Case | `/lecturer/case/create` | AI case generator, manual authoring, timeline builder, custom audit-log lines, question-PDF upload, password, open/close scheduling. |
| Reports | `/lecturer/case/{id}/reports` | Who filed and who hasn't. Grade, AI-evaluate, authorship panel, export one-page PDF. |
| Gradebook | `/lecturer/gradebook` | Learner × case matrix, class grade, per-case averages, CSV export. |
| Progress | `/lecturer/progress` | Live monitoring: who's active, who's stalled 5+ days, who hasn't started. Per-student drill-down. |
| My Students | `/lecturer/students` | Assign students to yourself. **View as** any assigned student. |

---

## AI setup

AI case generation and report evaluation call [Groq](https://console.groq.com)
through a small Cloudflare Worker proxy rather than calling Groq directly.
This is a deliberate workaround: Render's free-tier web services share a
dynamic outbound IP pool with every other customer in the same region, and
calls made directly from Render to Groq (and previously to Gemini) failed
intermittently with authentication errors that had nothing to do with the
key itself — most likely because some IP in that shared pool has been
flagged by the provider's anti-abuse systems. Routing through a Cloudflare
Worker sidesteps the problem entirely, since the Worker calls Groq from
Cloudflare's network instead of Render's.

The **real** Groq API key lives only in the Worker's own secret store
(`wrangler secret put GROQ_API_KEY`), never in this app's `.env` or Render's
environment variables. This app only holds a `GROQ_PROXY_SECRET` — a
shared secret that authenticates requests to the Worker so a stranger who
finds the Worker's URL can't spend your Groq quota.

The Worker source lives in this repo at [`cloudflare-worker/`](cloudflare-worker/) —
see its README for how to deploy or rotate it.

---

## Authorship integrity — read this before your viva

The system does **not** contain an AI-text detector, deliberately.

Statistical AI detectors are unreliable enough that acting on them is unsafe.
OpenAI withdrew its own classifier for low accuracy, and published evaluations
show elevated false-positive rates for writers whose first language is not
English — which describes most of a Malaysian cohort. A false accusation is a
serious harm, and "the tool said so" is not a defensible basis for it.

What the platform does instead is record **how the text entered the editor**:

| Signal | Meaning |
|---|---|
| Paste share | Proportion of final length that was pasted rather than typed |
| Large pastes | Individual paste events over 220 characters |
| Typing ratio | Keystrokes ÷ characters. Normal writing exceeds 1.0 because of corrections |
| Active time | Editing time, excluding idle tabs |
| Revisions | Number of distinct saves |
| Peer similarity | Jaccard overlap on 5-word shingles against other submissions on the same case |

These are facts about composition, not inferences about authorship. The lecturer
sees them with a band — *nothing unusual* / *read with context* / *worth
discussing* — and the system **never blocks a submission and never accuses
anyone**. Peer similarity in particular is fully explainable and reproducible,
which is what makes it defensible.

Telemetry is browser-reported and therefore not tamper-proof. That limitation is
stated in the UI, and is the honest position to take in your write-up.

---

## Timed sessions

`publish_at` / `close_at` are evaluated on read via the `available()` scope —
no cron, no queue worker, nothing extra to run on XAMPP.

---

## File uploads

Question sheets and answer PDFs are stored under `storage/app/` and served
through authenticated controller routes. They are never publicly accessible.
10 MB limit, PDF only.

If uploads fail, check `php.ini`:
```
upload_max_filesize = 12M
post_max_size = 12M
```

---

## View as student

A lecturer can open any assigned student's view with one click. The original
identity is held in the session, a red banner shows across every page, and both
entering and leaving are written to the activity log. Lecturers can only
impersonate students assigned to them.

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| AI buttons do nothing | Set `GROQ_PROXY_URL` / `GROQ_PROXY_SECRET` in `.env`, then `php artisan config:clear` |
| Uploads fail | Raise `upload_max_filesize` / `post_max_size` in php.ini |
| Blank page after changes | `php artisan optimize:clear` |
| Case not visible to students | Check it is published **and** inside its open/close window |

---

## Stack

Laravel 12 · MySQL 8 · Tailwind (CDN) · Alpine.js · Chart.js ·
barryvdh/laravel-dompdf · Groq API · PHPUnit + Dusk · GitHub Actions · Pint
