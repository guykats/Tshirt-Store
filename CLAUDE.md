# Working in this repo

Tshirt Store — a bilingual (English/Hebrew) Jewish-identity apparel e-commerce
site. Laravel 13 API + React 18 SPA, deployed to store.guykats.com. For stack,
architecture, and local setup, read `README.md` first — this file is about
*how to work in this repo*, not what it is.

If you're picking this project back up with no prior context: read this file,
then `README.md`, then open `/dashboard/progress` (or query the `project_tasks`
and `epics` tables) to see what's actually in flight — that board is the live
source of truth for project state, not this file. Nothing here should be
treated as a snapshot of "current progress"; it goes stale, the board doesn't.

## The agent/skill system

Work on this project is organized as a small Jira-style board, not ad hoc:

- **`project_tasks` table** (`/dashboard/progress`) — concrete, shippable
  tasks. Each has an `agent_name` (`Dev Agent`, `Creative Agent`, `Ops Agent`),
  a `status` (`todo` → `in_progress` → `done`, or `blocked`), and — once done —
  a real `commit_sha` and optionally a `screenshot_path` as evidence. Nothing
  is marked done on self-report; it's tied to a verifiable commit.
- **`epics` table** (Epics section, same page) — bigger strategic initiatives
  proposed by the Visioner Agent. A human explicitly **chooses** (approves),
  **rejects**, or **delays to the end** each one from the UI. Only once
  approved does a task-breakdown happen — the epic's `id` shows up as
  `epic_id` on the `project_tasks` rows it spawns.
- **`.claude/agents/{dev,creative,ops,visioner}-agent.md`** — real, invokable
  subagent definitions for the four roles above. Use the `Agent` tool with
  `subagent_type: "dev-agent"` (etc.) rather than doing the work as an
  undifferentiated generalist when a task is already scoped to a role.
- **`.claude/skills/ship-project-task/SKILL.md`** — the exact start → build →
  verify → complete → push procedure for any `project_tasks` item. Load this
  before picking up work; don't improvise a different workflow.
- **`.claude/skills/propose-epics/SKILL.md`** — how the Visioner Agent
  researches and seeds new epics. Only proposes; never implements.

**There is no separate task-runner.** "Updating the board" always means
*writing and pushing a Laravel migration* — see "Production state changes
through git" below.

### Autonomous runs

`.github/workflows/pm-agent.yml` runs this exact PM workflow unattended on a
30-minute cron, independent of any interactive session or whether the
owner's machine is on — it reads this file, checks the board, ships or
seeds work, and pushes to `main` on its own. If you're starting an
interactive session, `git log` / the board may already reflect work you
didn't do — that's expected, not a conflict to resolve. It authenticates
with an `ANTHROPIC_API_KEY` repo secret and is capped at `--max-turns 30`
per run as a cost/blast-radius bound; adjust that (or disable the workflow
entirely) rather than removing the cap if it needs tuning.

## Standing operating agreement with the project owner

These were granted explicitly during earlier sessions and remain in force
unless the owner says otherwise:

- **Keep working continuously without waiting for prompts.** The owner wants
  an always-nonempty backlog across multiple agents/roles, refilled proactively
  before it empties — not one task at a time, and not paused waiting for
  "should I keep going?" confirmation.
- **Direct push/merge to `main` is authorized** for this ongoing work — main
  auto-deploys via `deploy.yml`. This does not extend beyond this repo or
  beyond the kind of routine feature/fix/infra work already being done this
  way; anything unusually risky still warrants a check-in.
- **Hostinger's SSH deploy step sometimes throws transient network/timeout
  errors** unrelated to actual deploy correctness — don't treat those as a
  real failure to chase down; they're infra flakiness, not a code problem.
- **PayPal and SMTP real credentials are intentionally deferred** — the owner
  provides them later. Build and ship everything else in the meantime using
  mocks (`Mockery` in tests) and `MAIL_MAILER=log` in dev; don't block on
  missing secrets, and never ask the owner to paste real secrets into chat.
- **GitHub access is scoped to `guykats/tshirt-store` only.**

## Production state changes through git — always

`deploy.yml` does nothing but SSH in, `git reset --hard` to the pushed commit,
`composer install`, `php artisan migrate --force`, `storage:link`. There is no
other command-execution path in production. Concretely this means:

- Seeding, backfilling, or updating any row (including the `project_tasks` /
  `epics` board itself) is a **data-only migration** (`DB::table()->insert()`
  / `->update()`), not a one-off script or a manual DB edit.
- The deploy wraps the app in maintenance mode with a bash
  `trap 'php artisan up || true' EXIT`, so a failed step can never leave the
  site stuck down. Preserve that guarantee if you touch `deploy.yml`.

## Hard-won conventions and gotchas

- **Models use `#[Fillable([...])]` PHP attributes**, not the `$fillable`
  property. Follow the existing pattern in `app/Models/*.php`.
- **SQL must run on SQLite too** (tests use it, prod uses MySQL) — no
  `FIELD()`; use `CASE column WHEN 'x' THEN 0 ... END` for custom ordering.
- **`RefreshDatabase` re-runs every historical migration**, including data
  backfills into `project_tasks`/`epics`. A test asserting an exact row count
  on either table must clear it first (`ProjectTask::query()->delete();`).
- **Commit-sha discipline:** when a migration references a commit hash as
  evidence, get it from `git rev-parse HEAD` and verify the length is exactly
  40 (`python3 -c "print(len(sha))"`) — don't eyeball it. The usual sequence
  is: commit the actual work → get its sha → write a *second* migration that
  marks the task done and references that sha → commit and push that.
- **Sanctum session gotcha:** `EnsureFrontendRequestsAreStateful` only starts
  a session for requests whose Origin/Referer host+port exactly matches
  `SANCTUM_STATEFUL_DOMAINS`. Ad hoc `php artisan serve --port=<random>` will
  silently fail login with no session — use `--port=8000` or `5173`, both of
  which are already whitelisted, for any manual/Playwright verification.
- **Screenshots as evidence** live in `storage/app/public/task-screenshots/`,
  carved out of the default `storage/app/public/.gitignore` (`*` /
  `!.gitignore`) with explicit `!task-screenshots/` and `!task-screenshots/**`
  exceptions — this repo has no object storage, screenshots reach production
  by being committed. `ProjectTask::screenshotPath()` validates the format
  (`task-screenshots/<name>.(png|jpe?g)`, no `..`).
- **Bilingual by default:** every user-facing string needs both an English and
  a real (not literal-translation) Hebrew entry in `resources/js/i18n/index.js`.
- **Accessibility is not optional:** paired `<label htmlFor>`/`id`,
  `role="alert"` on error text, `aria-label`/`role="img"` vs `aria-hidden` on
  meaningful vs. decorative SVG (see `DesignArt.jsx`'s `label` prop).
- **CI builds the frontend before testing** (`.github/workflows/tests.yml`
  runs `npm ci && npm run build` before `php artisan test`) because the root
  route needs a real Vite manifest to render at all.

## Verification bar before marking anything done

```bash
php -l <every changed .php file>
npm run build
rm -f database/database.sqlite && touch database/database.sqlite
php artisan migrate:fresh --seed --force
php artisan test
```

Plus a Playwright screenshot for anything with a UI. A task is not done until
these pass — see `.claude/skills/ship-project-task/SKILL.md` for the full
procedure.
