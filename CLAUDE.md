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
15-minute cron (`cron: '*/15 * * * *'` — shortened from an original 30
minutes because runs finish in ~3-13 min, well under the old cadence),
independent of any interactive session or whether the owner's machine is on
— it reads this file, checks the board, ships or
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
- **`deploy.yml` silently stopped auto-deploying on 2026-08-01 and is STILL
  broken as of 2026-08-14 (13+ days), tracked as blocked `project_tasks` id
  345 — treat this as a durable, human-blocked fact, not something to
  re-investigate each run.** Root cause (found 2026-08-04): GitHub does not
  create a new `push`-triggered workflow run for commits authored via
  `GITHUB_TOKEN`/an installation token, full stop — not just for commits
  touching workflow files. Every commit since `8dde7500`
  (2026-08-01T18:20:33Z, the last verified/GPG-signed commit) has landed
  with committer identity `claude[bot]`/unverified and triggered zero
  deploy runs. Consequence: the board itself is also frozen — any
  migration-seeded `project_tasks`/`epics` row created after 2026-08-01
  never reaches production, so a static-looking live dashboard is a
  *symptom* of the freeze, not an owner-review lag. Epics 7, 9, 15, 16, 18
  are the exception: they were committed 2026-07-27–07-31, *before* the
  freeze, so they did reach production and stay live-approvable — don't
  mistake continued activity on those old rows for evidence the freeze has
  lifted. The standard escape hatch (`gh workflow run deploy.yml --ref
  main`, normally exempt from this restriction) was tried and also fails
  with `HTTP 403: Resource not accessible by integration`, because
  `pm-agent.yml`'s `permissions:` block grants only `contents: write` +
  `id-token: write`, not `actions: write` — and granting that requires
  editing a workflow file, which no autonomous run can push (see the
  installation-token gotcha further below). This needs a human: either
  (a) unstick production now by manually running "Deploy to Production"
  from the Actions tab, or pushing any human-authored commit, and/or
  (b) durably fix it with a PAT (`repo`+`workflow` scope) as a new secret
  for `pm-agent.yml` to push with, or by adding `actions: write` + an
  explicit-dispatch step via the GitHub UI directly. **Every re-check from
  2026-08-04 through 2026-08-15 (dozens of checks across many runs, most
  recently ~04:20 UTC 2026-08-15) has found zero drift**: same deploy
  timestamp/sha (`8dde7500`, 2026-08-01T18:20:33Z), HEAD still unverified,
  no `actions: write` grant, no PAT, unapproved `todo` backlog steady around
  91. Given this, **seeding more ad hoc backlog is optional, not mandatory,
  whenever `deploy.yml` is stale (>1 day) and the unapproved queue is
  already substantial (tens of items)** — production can't receive new rows
  anyway, so more seeding just burns turns on output nobody can act on.
  **CORRECTION (2026-08-15, ~04:20 UTC): stop pushing a "re-verify zero
  drift" commit every run — this had become its own instance of the exact
  waste this paragraph warns against.** Between 2026-08-10 and this
  correction, 69 separate commits (`git log --oneline | grep -i
  "drift\|re-verify"`) did nothing but bump this paragraph's timestamp,
  sometimes less than 20 minutes apart. Each one is a normal push to
  `main`, and `tests.yml` triggers on every push to `main`/`master`/`*.x`
  with no path filter (confirmed in `.github/workflows/tests.yml` — no
  `paths:` key under `on.push`), so every single one of those 69 commits
  also kicked off a full CI test run (frontend build + `php artisan test`)
  for a documentation-only timestamp edit. Nobody can add a `paths:` filter
  to fix this at the source — that's a workflow-file edit, and no
  autonomous run can push to `.github/workflows/*.yml` (see the
  installation-token gotcha below) — so the fix has to be in *how often
  this paragraph gets touched*, not in CI config. **New rule: only push a
  commit updating this paragraph if either (a) something actually changed
  — a new deploy sha, HEAD verified, an `actions: write`/PAT grant
  appearing, or the epic-oscillation state flipping — or (b) it's been
  over 12 hours since the timestamp this paragraph currently records.**
  Anything short of that, a run that re-checks and finds zero drift should
  say so in its own summary and move on without touching this file. This
  is a stricter instance of the turn-budget-discipline lesson above
  (runs #129/#130): a clean no-op check is a good outcome and doesn't need
  a commit to prove it happened. **Last re-check: 2026-08-18, ~03:08 UTC —
  deploy state still zero drift, but epics 7/9/15/16/18 flipped back to
  `proposed`** (same deploy sha `8dde7500`/2026-08-01T18:20:33Z, HEAD still
  unverified, no `actions: write` grant, no PAT, unapproved `todo` backlog
  still 91, task 348 still `todo`/unapproved). This edit is happening under
  rule (a) — the epic-oscillation state flipped since the prior check
  (2026-08-17 ~18:20 UTC recorded all 5 as still `approved`); this check
  found all 5 back at `proposed`, linked tasks still intact and `done`.
  Per the note below, this flip is expected, unfixed task-348 behavior, not
  a new bug or a sign the freeze lifted — recorded here only because the
  rule explicitly treats an oscillation flip as a reportable change, not
  because it needed new investigation. No approved `todo` task and no
  approved epic-awaiting-breakdown existed this run either way, so nothing
  was shippable; per CLAUDE.md's "optional, not mandatory" guidance the
  91-item unapproved backlog was left as-is rather than padded further.
  Separately: epics 7, 9, 15, 16, 18 have been observed oscillating between
  `approved` (with linked tasks intact) and `proposed` across different
  checks with no consistent direction — this is **task 348**
  (`EpicController::approve/reject/delay` have no guard on an epic's
  current status, so any direct API call can silently revert an
  already-approved, already-built epic back to `proposed`), still
  `todo`/unapproved as of 2026-08-14. Don't re-diagnose this oscillation as
  a new bug or as evidence of a real owner review pass each time it's
  seen — it's the known, unfixed task 348, orthogonal to the deploy freeze
  itself.
- **The freeze has a second, cascading effect: `pm-agent.yml`'s own idle
  self-disable check can never fire while any pre-freeze-approved epic
  remains un-deployed, so the cron keeps firing every 15 minutes
  indefinitely for zero shippable output (confirmed 2026-08-10).**
  `PmAgentAutomationController::disableIfIdle()` only disables the workflow
  if there's no approved todo task *and* no approved epic with zero linked
  tasks (`hasApprovedEpicAwaitingBreakdown()`), evaluated against
  production's frozen-since-2026-08-01 DB. The breakdown migrations that
  gave epics 7, 9, 15, 16, 18 their `project_tasks` rows all landed *after*
  the freeze commit, so none of them ever deployed — production still sees
  all 5 as approved with zero linked tasks, exactly the condition that
  keeps `disabled:false, reason:"epic_awaiting_breakdown"` firing every
  run regardless of how thin the real local backlog is. This is an
  expected, harmless-but-wasteful side effect of task 345, not a separate
  bug — don't open a new blocked task or patch
  `hasApprovedEpicAwaitingBreakdown()` for it; the fix is identical to
  345's (get a real deploy through). A run seeing a string of near-empty,
  few-turn `pm-agent.yml` runs with a stable `epic_awaiting_breakdown`
  reason and no new commits should recognize this pattern immediately
  rather than re-diagnosing it from scratch.

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
- **No session — interactive or the `pm-agent.yml` cron itself — can push
  changes to `.github/workflows/*.yml` under the current setup.** GitHub
  rejects the push server-side with `refusing to allow a GitHub App to
  create or update workflow ... without workflows permission`, even though
  the same push succeeds for other files in the same commit. First hit
  2026-08-03 from an interactive session using a GitHub App installation
  token (`x-access-token:ghs_...` in `git remote -v`) trying to fix a stale
  "30-minute cron" comment inside `pm-agent.yml`'s own embedded prompt:
  `CLAUDE.md` pushed fine, the workflow-file edit had to be reverted into a
  separate commit. That entry used to claim this was specific to
  interactive installation-token auth and that `pm-agent.yml`'s own run
  (authenticating via `secrets.GITHUB_TOKEN` inside a real Actions job) was
  exempt, citing commits `8dde750`/`692cfd3` as precedent — a live
  2026-08-04 test from inside an actual `pm-agent.yml` run (confirmed via
  `GITHUB_ACTIONS=true` and a `ghs_...` token) disproved that: a trivial,
  fully-reverted probe push to `pm-agent.yml` on a throwaway branch got the
  exact same rejection. The real cause is simpler and applies to both auth
  paths identically: `pm-agent.yml`'s own `permissions:` block only grants
  `contents: write` and `id-token: write` — no `workflows: write` — so the
  `GITHUB_TOKEN` minted for that job structurally cannot touch workflow
  files, regardless of who or what triggered the run. (Whatever let
  `8dde750`/`692cfd3` through was some other configuration or credential at
  the time, not a property of this auth path in general — don't treat it as
  current precedent.) If a workflow-file edit is genuinely needed, land the
  content change in a non-workflow file (or describe it in a commit message
  / task note) for the owner to apply manually — no autonomous run can do
  it under the current permissions, full stop.
- **`pm-agent.yml`'s "Configure local environment" step only overrides
  `DB_CONNECTION`, not `DB_DATABASE`, so the PM Agent's local board replica
  is silently written to the wrong file.** `.env.example` sets
  `DB_DATABASE=tshirt_store` for its MySQL example config; the sqlite
  connection in `config/database.php` also reads `DB_DATABASE` (Laravel
  convention — `env('DB_DATABASE', database_path('database.sqlite'))`), so
  when the step does `cp .env.example .env` +
  `sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env` without also
  clearing `DB_DATABASE`, every `php artisan migrate` in that run creates/
  uses a flat file literally named `tshirt_store` at the repo root instead
  of `database/database.sqlite` — `php artisan` commands all still work
  (Laravel's config resolution is internally consistent), but this repo's
  own documented verification bar (`rm -f database/database.sqlite && touch
  database/database.sqlite; php artisan migrate:fresh --seed --force`) and
  any direct `sqlite3 database/database.sqlite ...` inspection silently
  target the wrong, empty file and report `no such table`. The stray
  `/tshirt_store` file has been gitignored since the initial commit — so
  the symptom was already being tolerated, not fixed. README.md's own local
  sqlite instructions get this right ("`DB_CONNECTION=sqlite` (remove/ignore
  the other DB_* vars)"); `pm-agent.yml`'s step doesn't follow that pattern.
  Root-caused during the 2026-08-04 run after `migrate:fresh --seed`
  reported success but a direct board query still failed — fixing it means
  adding a `DB_DATABASE` clear/override alongside the existing
  `DB_CONNECTION` sed in `pm-agent.yml`'s "Configure local environment"
  step (mirroring `phpunit.xml`'s `DB_DATABASE=":memory:"` override, which
  already does this correctly), but that's a workflow-file edit blocked by
  the installation-token restriction just above when hit from a Claude Code
  session — needs the owner or a `secrets.GITHUB_TOKEN`-authenticated
  in-Actions run to apply. Until fixed, any session (interactive or
  autonomous) that finds `project_tasks`/`epics` queries failing with
  `no such table` should check `.env`'s `DB_DATABASE` value and/or look for
  a stray `./tshirt_store` file before assuming the board is actually empty.
- **The stray `./tshirt_store` file above is not just misdirected — it is
  where that run's REAL `SyncApprovedTaskTitles`/`SyncEpicDecisions` output
  actually landed, so treat it as live data, not scratch, until you've read
  it.** A 2026-08-06 run hit the `no such table` symptom, "fixed" it by
  `sed`-ing `.env`'s `DB_DATABASE` to `database/database.sqlite`, then ran
  `rm -f tshirt_store` followed by `php artisan migrate:fresh --seed --force`
  to get a working local replica — without ever inspecting the stray file's
  contents first. That deleted the one copy, anywhere in this run's
  filesystem, of the real production sync (both "Sync real ... from
  production" steps had already run against the misconfigured `.env` before
  the PM Agent step starts, per `pm-agent.yml`'s step order, so the sync
  output was already sitting in `tshirt_store` — not merely stale, but the
  actual data). Confirmed as a real loss, not a hypothetical: epic 18
  ("Epic Build-Status Rollup") already had fully-`done` linked tasks built
  from an earlier run, which per the approval gate in step 3 of this prompt
  is only possible if it was really `approved` on production — yet with the
  sync gone, the freshly-replayed local row read back `proposed`, the
  migration-only default. Worse, this can't be repaired mid-run:
  `PM_AGENT_BOARD_TOKEN` is only passed via `env:` to the two dedicated sync
  steps in `pm-agent.yml`, not to the "Run PM Agent" step, so a session at
  that point has no credential to re-fetch what it just deleted — it's
  gone until the next scheduled run re-syncs from scratch. The safe recovery
  when you hit this is: **never `rm` the stray file blindly** — first run
  `sqlite3 tshirt_store ".tables"` and `sqlite3 tshirt_store "SELECT title
  FROM project_tasks WHERE approved_for_dev=1"` / `SELECT title, status FROM
  epics"` against it *in place* to read the real synced approvals/decisions,
  cross-reference those against the freshly-migrated `database/database.sqlite`
  before trusting the latter for task selection, and only delete the stray
  file once you've confirmed you don't need it (or better, leave it alone —
  it's gitignored and harmless to leave on disk). If you've already deleted
  it before reading it, treat this run's approval visibility as degraded
  exactly like a failed sync (`continue-on-error` already treats that as a
  harmless no-op that falls back to migration-only state) — do not guess an
  epic or task into "approved," and do not compensate by force-seeding extra
  backlog, since the actual gap is visibility, not backlog volume. The
  underlying fix is the same pending workflow-file edit noted above (add a
  `DB_DATABASE` clear to `pm-agent.yml`'s "Configure local environment" step
  before the migrate/sync steps run, not after) — until a human applies
  that, this failure mode recurs every single run, not just when someone
  goes looking for the stray file.
- **`env()` does not observe a test's runtime `putenv()` call in this Laravel
  13.20/phpdotenv 5.6 stack — use `$_ENV`/`$_SERVER` instead.** Confirmed via
  `php artisan tinker --execute="putenv('X=v'); var_dump(getenv('X'));
  var_dump(env('X'));"`: `getenv()` sees the putenv()'d value, `env()` returns
  `''` regardless, because Laravel's `env()` resolves through phpdotenv's
  Repository (backed by `$_ENV`/`$_SERVER`), which a runtime `putenv()` call
  never populates. A real process-level env var set *before* the PHP process
  starts (`X=v php artisan ...` — which is exactly how a GitHub Actions
  `env:` step block supplies a secret to `php artisan <command>`) IS observed
  correctly. This means any test that tries to simulate an env-var-gated code
  path with `putenv('KEY=value')` mid-process will silently exercise the
  "env var absent" branch instead of the intended one — the test can still
  pass (if that branch's behavior happens to match the assertion) or fail in
  a way that looks like a production/functional regression when it's really
  just an untestable-as-written test. Hit exactly this on task "SyncApproved
  TaskTitles and SyncEpicDecisions no longer actually sync on a clean
  checkout" (`tests/Feature/Console/SyncApprovedTaskTitlesCommandTest.php`,
  `SyncEpicDecisionsCommandTest.php`): 4 tests using `putenv('PM_AGENT_BOARD_
  TOKEN=board-secret')` fail because the command under test never sees a
  non-empty token and always takes the early-return path — the original task
  description (written before this was root-caused) worried this meant
  `pm-agent.yml`'s real production sync was broken too, which a 2026-08-19
  investigation ruled out: the workflow sets the token as a genuine step-level
  `env:` var, which `env()` reads fine (verified directly). Fix tests like
  this by setting `$_ENV['KEY']`/`$_SERVER['KEY']` directly (and unsetting in
  teardown) instead of `putenv()`. Don't "fix" the production code path for a
  failure that's actually a test-technique bug — verify which side is broken
  (as above) before assuming a red test means a real regression.
- **CORRECTION (2026-08-09, ~23:19 UTC): the "confirmed transient blip"
  conclusion previously written here was premature — this is a real,
  persisting data reversion on production, root-caused and now tracked as
  task id 348, not a self-resolving network hiccup.** The earlier version of
  this entry closed the investigation after one run (`.../jobs/93297084169/logs`,
  20:23 UTC) logged `Synced 12 epic(s), inserted 0 live-only epic(s) from
  production.` and treated that as proof the all-`proposed` symptom had
  self-resolved — but that log line only proves the sync *mechanism*
  reached production successfully; it says nothing about whether the actual
  approved epics came back. They didn't. A run-by-run check of every
  `pm-agent.yml` run from 21:27 through 22:55 UTC that same day (`gh run
  view <id> --log | grep -i "synced\|real approved todo"`) showed **five
  consecutive, fully successful** syncs (`Synced 12 epic(s)...`, never
  "Could not reach") each still reporting all 12 epics at `status=proposed`
  and 0 approved todo tasks — a stable, persisting state over 90+ minutes,
  not an intermittent blip. Root cause: `EpicController::approve()`,
  `::reject()`, and `::delay()` (`app/Http/Controllers/Api/EpicController.php`)
  have no guard on the epic's current status — `delay()` unconditionally
  sets `status = 'proposed'` regardless of what it was before. The
  `Epics.jsx` frontend only renders the delay button when
  `epic.status === 'proposed'`, so a normal dashboard click can't trigger
  this on an already-`approved` epic — but the route
  (`POST /api/epics/{epic}/delay`) itself has no server-side check, so any
  direct/programmatic call against an approved epic (even one with real
  linked `done` tasks, like ids 7, 9, 15, 16, 18 as of 2026-08-06) silently
  reverts it, discarding the approval with no guard, log distinction, or
  recovery path. This exact bug is already tracked as **task id 348**
  ("EpicController::approve/reject/delay have no guard on the epic's
  current status..."), `todo`/`approved_for_dev=0` — seeded by a later run
  (`database/migrations/2026_08_20_100000_seed_visioner_history_epic_guard_backlog.php`)
  that independently re-discovered and root-caused this same symptom, but
  never circled back to correct this entry, which is exactly the kind of
  silent non-fix step 7 of the standing run prompt exists to prevent. Don't
  re-investigate this mystery from scratch again: if a future run's local
  epics table shows previously-approved epics back at `proposed` after a
  clean, successful sync, that is expected and already explained until task
  348 ships and (separately, a human/owner call, not something to force via
  migration) any wrongly-reverted epics are explicitly re-approved from the
  dashboard.

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
