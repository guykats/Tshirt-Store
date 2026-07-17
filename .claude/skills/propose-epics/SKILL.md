---
name: propose-epics
description: The Tshirt Store repo's procedure for the Visioner Agent to research and propose new strategic epics into the epics board, for the human owner to approve/reject/delay. Use when asked to propose what's next at a strategic level, or when the proposed-epics queue is empty or thin.
---

# Proposing epics

Epics are strategic initiatives — bigger than a single `project_tasks` item, spanning multiple tasks and possibly multiple agents once approved. They live in the `epics` table and are surfaced in the Epics section of `/dashboard/progress`, where a human chooses (`approve`), rejects, or delays each one. This skill only ever proposes; it never implements anything or creates `project_tasks` rows directly.

## 1. Check for duplicates and current state

Before proposing, look at what already exists so you don't re-propose something pending or done:

```php
DB::table('epics')->select('title', 'status')->get();
DB::table('project_tasks')->select('title', 'status')->get();
```

## 2. Research, don't just assert

Use WebSearch to ground each proposal — current DTC/e-commerce growth patterns, what investors actually look for, or the specific domain (Judaica / cultural-identity retail) — rather than presenting personal taste as a finding. A one-line grounding note belongs in the epic's `description`.

## 3. Write good candidates

A good epic:
- Is a real initiative (a new channel, a new product capability, a new retention/acquisition lever) — not a UI tweak (that's a `project_tasks` item).
- Is scoped enough that, once approved, a PM could break it into concrete buildable tasks without re-inventing it.
- Fits the business's actual current stage (small, single-catalog, no real traction data yet) — don't propose things that only make sense several stages later.

Propose several at once (4-6 is a good batch size) so the board always has a real backlog of decisions to make, not a single item at a time.

## 4. Seed via migration

Same rule as everywhere else in this repo: production state only changes through a git-tracked migration.

```php
$now = now();

DB::table('epics')->insert([
    'title' => 'Short, concrete epic title',
    'description' => 'What it is, why it matters, one line of research grounding it.',
    'agent_name' => 'Visioner Agent',
    'status' => 'proposed',
    'priority' => 0,
    'decided_by' => null,
    'decided_at' => null,
    'created_at' => $now,
    'updated_at' => $now,
]);
```

Lint the migration (`php -l`), rebuild the local db (`migrate:fresh --seed --force`), run the full test suite, then commit and push.

## 5. Then stop

Do not act on your own proposals. The human decides via the board's Choose it / Reject / Delay to end actions. Once an epic is approved, breaking it into `project_tasks` (with `epic_id` set) and assigning them to Dev/Creative/Ops Agent is the PM's job, not the Visioner Agent's.
