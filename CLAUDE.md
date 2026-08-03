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
  `todo` tasks also carry an `approved_for_dev` boolean that the owner flips
  on from an "Approve for development" button on each `todo` row in the
  board UI. This is a hard human gate: nobody — not the autonomous cron, not
  an interactive session, not a subagent — should start building a `todo`
  task whose `approved_for_dev` is still `false`. Ad hoc backlog tasks
  seeded outside of an epic breakdown start unapproved by design; being
  created is not the same as being cleared to build. Tasks freshly broken
  out of an **approved** epic are the one exception and default to
  `approved_for_dev = true` — the owner's decision to approve the epic
  itself already is the build decision, by explicit owner request.
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
via `CLAUDE_CODE_OAUTH_TOKEN` (the owner's Claude subscription, generated
with `claude setup-token` — not the metered `ANTHROPIC_API_KEY`, which is
kept as a secret only as a fallback) and is capped at `--max-turns 150` /
`timeout-minutes: 90` per run as a blast-radius bound, not a cost one now
that it's subscription-authenticated; adjust that (or disable the workflow
entirely) rather than removing the cap if it needs tuning.
- **Turn-budget discipline is explicit in the prompt on purpose.** Runs #129
  and #130 both hit `error_max_turns` at exactly 101 turns with nothing
  shippable to show for it — root-caused via a one-off `show_full_output:
  true` diagnostic run (reverted immediately after) to an earlier version of
  this prompt that said "use as much of your turn budget as there is work
  available — don't stop after one small change," which pushed the agent to
  start a second/third approved task without checking whether it could
  actually finish it. The prompt now says the opposite: shipping exactly one
  task cleanly is a full success, and starting work you're not confident you
  can finish (implement + full verification bar + commit + push + mark done)
  is the failure mode to avoid, not idling. If stuck runs recur, that
  discipline — not just the numeric cap — is the first thing to revisit.
- **When a run discovers a durable, repeatable gap, write the lesson down —
  don't just route around it once.** A missing tool capability, a false
  assumption baked into this file/a skill/an epic, a process step that
  doesn't actually work as documented — if it would bite a *future* run the
  same way, silently working around it (or worse, quietly giving up with
  nothing shippable) throws the lesson away. Add it to this file — the
  "Hard-won conventions and gotchas" section for a concrete gotcha, this
  section for a process-level one — as part of the same run, committed and
  pushed, the same way the turn-budget lesson above was captured after runs
  #129/#130. This applies to interactive sessions too, not just the cron:
  first concrete case, added below — the PM Agent's own `--allowedTools`
  has no `WebFetch`, so a task/epic that assumes it can fetch a real
  external file (an image, a dataset, anything not reachable by a Bash
  `curl` to a URL already pinned in the task itself) will silently stall
  with nothing pushed, not even a `blocked_reason`, unless the task is
  scoped to only need tools it actually has. A task genuinely blocked for a
  structural reason like this must still get `status = 'blocked'` with a
  clear `blocked_reason` (per the ship-project-task skill) — silently
  spending turns and reporting `success` with zero commits is never
  acceptable, run it as a failure to surface, not a quiet no-op.

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
- **Two separate `ANTHROPIC_API_KEY` secrets exist, in two different places:**
  a GitHub Actions repo secret (used by `pm-agent.yml` in CI) and a
  production `.env` value (used live by `app/Services/AnthropicClient.php`
  for `/dashboard/chat`'s Visioner Agent chat). They can hold the same key
  value, but setting one does not set the other — check both if either
  integration isn't working. Same rule as PayPal/SMTP: never ask the owner
  to paste the key into chat, only tell them where to add it.
- **`GITHUB_ACTIONS_TOKEN` is another deferred credential**, same rule as the
  above: needed for the "PM Agent automation" enable/disable control on
  `/dashboard/progress` (`app/Services/GitHubActionsClient.php`,
  `PmAgentAutomationController`) to actually call the GitHub Actions API and
  flip `pm-agent.yml` on/off. Needs a GitHub personal access token (classic,
  `workflow` scope, or a fine-grained token with "Actions: Read and write" on
  this repo) added to production `.env` — the control shows a clear "not
  configured" state and does nothing destructive until it's set. Same
  `config:cache` gotcha as the other `services.php`-backed secrets.
- **`PM_AGENT_BOARD_TOKEN` is a shared secret, not an independent credential**
  — unlike every other secret above, the *same* value must be set in two
  places: production `.env` and the `PM_AGENT_BOARD_TOKEN` GitHub Actions
  repo secret. Why this exists: the CI job never has real production DB
  access — it only ever sees a disposable local SQLite rebuilt from migration
  history, which has no idea a human just clicked "Approve for development"
  on the live dashboard (that click writes straight to production MySQL,
  nothing else reads it). This token authenticates two token-gated (not
  Sanctum) endpoints on `PmAgentAutomationController` that close that gap:
  - `POST /api/pm-agent-automation/disable-if-idle` — asks production
    whether any `todo` task is really `approved_for_dev` right now, and
    disables the `pm-agent.yml` workflow server-side (so the `SystemEvent` it
    logs lands in the real audit log, not a throwaway CI database) if not.
  - `GET /api/pm-agent-automation/approved-todo-titles` — exports the real
    set of approved todo task titles; `pm-agent.yml`'s "Sync real
    approved-task titles from production" step fetches this and overwrites
    the CI job's freshly-replayed local `project_tasks.approved_for_dev`
    flags to match, via `app/Console/Commands/SyncApprovedTaskTitles.php`,
    *before* the PM Agent starts deciding what to build. This is what fixes
    the actual task-selection gap, not just the idle-check.
  - `GET /api/pm-agent-automation/epic-decisions` — the same fix for `epics`:
    approve/reject/delay on the live Epics tab has the identical
    live-click-invisible-to-CI problem, *plus* epics can be created live too
    with no migration at all (`VisionerChatController::proposeEpic`, the
    `/dashboard/chat` Visioner conversation), so a replay can be missing rows
    entirely, not just have their status wrong. `pm-agent.yml`'s "Sync real
    epic decisions from production" step (`app/Console/Commands/SyncEpicDecisions.php`)
    updates locally-known epics and inserts live-only ones. If the PM Agent
    then acts on a synced-in epic that has no real migration yet (breaking it
    into `project_tasks`), it must first push a migration inserting the epic
    itself before referencing its id anywhere — the workflow prompt spells
    this out (step 3) — otherwise the reference would dangle on the next
    fresh replay, since that local sync-only id only exists for one CI run.

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
- **New board-seeding migrations must be dated after the newest existing
  migration filename, not after today's real-world date.** A one-time
  historical migration, `2026_08_04_100000_reset_board_clear_non_done_tasks_and_epics.php`,
  does an owner-requested `DB::table('project_tasks')->where('status', '!=',
  'done')->delete()` + `DB::table('epics')->delete()`. That's correct as a
  one-off past event, but `migrate:fresh` replays full migration history in
  filename order every time — so any new seed migration whose timestamp
  sorts *before* that reset (e.g. because it was dated off the real
  calendar date instead of the repo's already-far-future migration
  timeline) gets its rows silently deleted by the replay before you ever
  see them. This bit a live run: a migration dated `2026_08_03_270000` ran
  successfully (recorded in the `migrations` table, no error) but its 3
  inserted rows were gone after `migrate:fresh --seed`, because the repo's
  newest migration at the time was already dated `2026_08_15_100000`.
  Always check `ls database/migrations | tail -5` (or equivalent) and pick
  a timestamp after that, not after `date`'s real output, before writing a
  new seed/backfill migration — and always re-query the actual inserted
  rows by title after `migrate:fresh --seed`, not just a clean migration
  run, before trusting a seed migration worked.
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
- **`git worktree remove` can silently no-op:** it sometimes reports success
  (or hits the command timeout) without actually deleting the checkout —
  `git worktree list` then just shows the entry as `prunable` while the full
  directory (including its own independently-installed `node_modules`/
  `vendor`) is still sitting on disk. This has bitten `npm test`/vitest,
  which globbed in stray `.test.ts` files from an orphaned worktree's
  `vendor/` tree. After removing a worktree, verify with `git worktree list`
  and `du -sh <path>` (or just `rm -rf` the directory once `git worktree
  list` no longer references it) rather than trusting the command's exit
  code.
- **Screenshots as evidence** live in `storage/app/public/task-screenshots/`,
  carved out of the default `storage/app/public/.gitignore` (`*` /
  `!.gitignore`) with explicit `!task-screenshots/` and `!task-screenshots/**`
  exceptions — this repo has no object storage, screenshots reach production
  by being committed. `ProjectTask::screenshotPath()` validates the format
  (`task-screenshots/<name>.(png|jpe?g)`, no `..`).
- **Bilingual by default:** every user-facing string needs both an English and
  a real (not literal-translation) Hebrew entry in `resources/js/i18n/index.js`.
- **The PM Agent has no `WebFetch`** — `pm-agent.yml`'s `--allowedTools` is
  `Read,Write,Edit,Bash,Grep,Glob,WebSearch` only. `WebSearch` returns search
  results, it cannot download a file. Run #163 (the "Photographic Product
  Imagery Layer" task, sourcing real placeholder photography) spent 17 turns
  and ~$3 and pushed nothing — not even a `blocked` mark — almost certainly
  because the task assumed it could fetch a real external image with no
  concrete, Bash-`curl`-reachable URL pinned in the task description. When
  writing or breaking down a task/epic that needs an external asset, either
  pin an exact fetchable URL in the task itself, or scope it as something a
  human sources and hands off — don't leave "go find a real photo" open-ended
  for an agent that structurally cannot browse or download one.
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
