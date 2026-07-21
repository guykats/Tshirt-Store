---
name: ship-project-task
description: The Tshirt Store repo's procedure for picking up a task from the project_tasks board (Dev/Creative/Ops Agent work) and shipping it — start-of-work signal, build, verify, evidence, completion signal, deploy. Use whenever starting or finishing any tracked task in this project, or when the project_tasks / epics tables need updating.
---

# Shipping a project_tasks item

This repo has no separate task-runner or agent infrastructure — the Jira-style board at `/dashboard/progress` (the `project_tasks` and `epics` tables) is just application data, and the *only* way to change production data is a git-tracked Laravel migration, because `deploy.yml` does nothing but `git reset --hard` + `migrate --force`. That's why "updating the board" always means "writing and pushing a migration," not calling an API by hand.

## 0. Only pick approved work

`project_tasks` has an `approved_for_dev` boolean, toggled from the board UI (`/dashboard/progress`) via an "Approve for development" button on `todo` rows — this is a deliberate human gate the project owner controls. **Never start building a `todo` task where `approved_for_dev` is `false`.** Query for the next task with `status = 'todo' AND approved_for_dev = 1` (ordered however you'd normally prioritize) and pick from that set only.

If there is no approved `todo` task available:
- Don't fall back to an unapproved one — that defeats the gate's purpose.
- It's still fine to do the *other* standing-backlog work that doesn't build product code: break an approved epic into concrete `project_tasks` rows, or seed a fresh batch of candidate tasks. Leave every task you create this way at the default `approved_for_dev = false` — creating a task is not the same as approving it, and newly seeded/broken-down tasks still need the owner's explicit approval before anyone builds them.
- Otherwise, stop for this run rather than inventing work to fill time.

## 1. Signal the start

Before writing any code, push a small migration that marks the task `in_progress`:

```php
DB::table('project_tasks')
    ->where('title', 'Exact task title')
    ->update(['status' => 'in_progress', 'updated_at' => now()]);
```

Commit and push this immediately, on its own — it should land on the board in real time, not retroactively once the work is already done. If the task doesn't exist yet (e.g. you're doing unplanned work), `insert` it as `in_progress` instead.

## 2. Do the work

Normal engineering/design practice for this repo:
- Models use `#[Fillable([...])]` attributes, not `$fillable`.
- SQL must run on SQLite (tests use it) — no `FIELD()`, use `CASE status WHEN 'x' THEN 0 ... END` for custom ordering.
- Every user-facing string needs both an English and Hebrew entry in `resources/js/i18n/index.js`, with real (not literal) Hebrew.
- If the task originated from an approved epic, set `epic_id` on the new `project_tasks` row(s) so it's traceable back to the epic on the board.

## 3. Verify before claiming done

Run all of these — a task is not done until they pass:

```bash
php -l <every changed .php file>
npm run build
rm -f database/database.sqlite && touch database/database.sqlite
php artisan migrate:fresh --seed --force
php artisan test
```

For anything with a UI, take a Playwright screenshot as evidence (see prior examples — `chromium.launch({ executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' })`). Note: ad hoc `php artisan serve` sessions only get a real Sanctum session if the port is in `SANCTUM_STATEFUL_DOMAINS` (`.env`) — use `--port=8000` or `5173`, not an arbitrary port, or login will silently fail.

`RefreshDatabase` re-runs every historical data migration, including backfills into `project_tasks`/`epics` — if a new test asserts an exact row count on either table, clear it first (`ProjectTask::query()->delete();` / `Epic::query()->delete();`) rather than assuming an empty table.

## 4. Commit the work, then signal completion

Commit the actual implementation first. Get its real commit hash and double-check the length (`python3 -c "print(len(sha))"` should print `40` — don't trust it by eye):

```bash
git rev-parse HEAD
```

Then write a second migration marking the task `done`, referencing that exact sha:

```php
DB::table('project_tasks')
    ->where('title', 'Exact task title')
    ->update([
        'status' => 'done',
        'commit_sha' => '<the 40-char sha from above>',
        'screenshot_path' => 'task-screenshots/some-name.png', // only if there's UI evidence
        'completed_at' => now(),
        'updated_at' => now(),
    ]);
```

Screenshots go in `storage/app/public/task-screenshots/` — that directory has explicit `.gitignore` exceptions (`!task-screenshots/`, `!task-screenshots/**`) carved out of the default `storage/app/public/.gitignore` (`*` / `!.gitignore`); nothing outside that carve-out reaches production. `ProjectTask::screenshotPath()` validates the format (`task-screenshots/<name>.(png|jpe?g)`, no `..`), so use a real matching filename.

Commit and push the completion migration. This is the point where the board's "Done" evidence — a real commit hash and a real screenshot — is something a viewer can independently verify, not a self-reported claim.

## 5. Keep the backlog non-empty

If, after finishing, there are no more `todo` tasks for any agent, don't stop and wait — propose and seed the next batch of real, concrete tasks (a small backlog-seeding migration, several items across agents) so there's always something in flight. This is a standing expectation for this project, not a one-time ask.
