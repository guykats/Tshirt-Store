# Project history and findings

A narrative record of what's been built, in what order, and why — for
anyone (human or agent) picking this project up without having watched it
happen. For live task-level status, use `/dashboard/progress`, not this file.
For operating rules and conventions, see `CLAUDE.md`.

## Phase 1 — Core schema and scaffolding

Laravel project scaffolded against the approved schema (users, products,
product variants, designs, orders, order items). Eloquent models built with
explicit relationships; migrations verified against MySQL for production.

## Phase 2 — Auth, API, and the SPA shell

Sanctum SPA (session-cookie) auth chosen over token auth, since this is a
single first-party frontend, not a public API. Policies added for the
human-in-the-loop approval pattern (designs and orders start in
`pending_approval` and need an admin action before they're live/active).
React + Vite frontend scaffolded; `app/Http/Controllers/Api/` and
`resources/js/pages/` established as the shape everything since has followed.

## Phase 3 — PayPal checkout

A small custom PayPal Orders v2 REST client (`app/Services/PayPalClient.php`)
was written instead of pulling in the official SDK — kept the dependency
surface small for a single, well-understood flow (create order → capture →
webhook confirmation). The webhook handler was later hardened to **fail
closed** on signature verification (reject on any doubt, rather than
defaulting to accept) once it became clear webhook forgery was a realistic
risk if this ever went to real payments.

## Phase 4 — System events and the first agent dashboard

`system_events` added as a permanent, append-only audit trail (every
approval/rejection/payment event logged). `agent_statuses` added alongside
it as a simple "what's the team doing" board on `/dashboard`. This board
was later superseded/unified — see "The board becomes real," below.

## Phase 5 — Invoices and order confirmation email

Bilingual PDF invoices (`barryvdh/laravel-dompdf`) and localized order
confirmation emails. Both were built to degrade gracefully without real
credentials (no SMTP configured → `MAIL_MAILER=log`, no crash) since PayPal
and SMTP credentials were — and still are, as of this writing — intentionally
deferred by the project owner to be supplied later.

## Reliability and security hardening pass

A dedicated pass fixed several real gaps found by inspection, not by
incident: a race condition in stock decrement during checkout (fixed with a
row-locked atomic update + a new `InsufficientStockException`), a product
`status` check missing from the public product-detail endpoint, PayPal
webhook signature verification that needed to fail closed rather than open,
missing rate limits on login/register (`RateLimiter::for()` in
`AppServiceProvider::boot()`), `TrustProxies` needed because production sits
behind a local nginx reverse proxy terminating TLS, and an order-approval
endpoint that wasn't idempotent. The deploy workflow was also wrapped in
Laravel maintenance mode with a `trap 'php artisan up || true' EXIT` so a
failed deploy step can never leave the site stuck in a 503 state.

## Design system and visual identity

A restrained parchment/ink/brass palette and serif/sans type system were
established (`resources/css/app.css`), and — deliberately — **no raster
product photography**. All product and brand imagery is minimalist,
single-stroke SVG line art (`resources/js/components/DesignArt.jsx`): Star of
David, Menorah, Chai, Hamsa, Pomegranate, Olive Branch, Hebrew script marks.
This was a considered choice for a small, principled-feeling brand, not a
placeholder — though "Real garment mockup imagery" is now a queued task
precisely because this reads as wireframe-y to someone unfamiliar with the
brand (see "Investor-readiness pass," below).

## Feature-test coverage pass

Auth, catalog, checkout, and design/order-approval flows all got feature
test coverage (`tests/Feature/Api/`), with PayPal calls mocked via `Mockery`
so the suite runs without real credentials. CI (`.github/workflows/tests.yml`)
needed an explicit `npm ci && npm run build` step added — the root route's
Blade shell needs a real Vite manifest to render, so `GET /` was 500ing on
every CI run until the frontend was built first.

## The board becomes real: `/dashboard/progress`

The original `agent_statuses` board only ever showed manually-typed status
text — not verifiable, and it drifted from what was actually happening. A
proper Jira-style board was built instead: `project_tasks` (title,
description, `agent_name`, `status`, and — critically — a real `commit_sha`
and optional `screenshot_path` as evidence, so "done" is never a
self-reported claim). The original `agent_statuses` "Agent Status" widget on
`/dashboard` was then **unified** with this data (`AgentStatusResource`
derives `current_task` live from `project_tasks` instead of accepting a
manually-typed value), so the two dashboards can't drift apart again.

A key process correction along the way: tasks must be marked `in_progress`
at the **start** of work (pushed as its own small commit) and the backlog
must be **kept continuously non-empty across multiple agents** — refilled
proactively before it runs out — rather than working one task at a time and
only recording it after the fact. This is now the standing expectation (see
`CLAUDE.md`).

## Feature build-out backlog (round 1)

Customer order history (`/orders`), catalog pagination, static Open
Graph/Twitter meta tags (social crawlers mostly don't execute JS, so these
can't be client-rendered), loading skeletons, an accessibility pass
(label/id pairing, `role="alert"`, meaningful vs. decorative SVG labeling),
and a brand-story `/about` page. All shipped through the same
start → build → verify → complete → push cycle now documented in
`.claude/skills/ship-project-task/SKILL.md`.

## Investor-readiness pass

Prompted by a request to make the site presentable for investor demos.
Researched what investors actually look for on an e-commerce site in the
first few seconds (a single clear value line, tight/consistent visual
language over feature-dumping, real trust signals, mobile-first speed), then
built a concept homepage mockup (rendered via Playwright from a standalone
HTML file, using the site's real CSS variables so it's a credible extension
of the existing brand rather than a generic template) and seeded a follow-up
backlog: implement the redesign, an admin Design Configuration panel
(logo/colors/hero/stats — the still-open "make the graphic design options
configurable" gap), real garment mockup imagery, a social-proof section
backed by real data instead of placeholders, a design-system style guide
page, and a mobile Lighthouse pass.

## Epic approval board

The backlog above is all task-level (single agent, single deliverable). For
bigger strategic bets — a new product capability, a new channel, a new
growth lever — a separate `epics` table and Epics section were added to
`/dashboard/progress`, proposed by a "Visioner Agent" and requiring an
explicit human decision (choose / reject / delay to the end) before any
child tasks get created. Six real candidate epics were seeded to start:
Custom Design Studio, International & Multi-Currency Expansion, Wholesale/B2B
Channel, Loyalty & Referral Program, Content & SEO Growth Engine, and an
Investor Traction Dashboard.

## Agent and skill definitions

The `agent_name` values used throughout the board (`Dev Agent`,
`Creative Agent`, `Ops Agent`, `Visioner Agent`) were, until this point, just
text labels — there was nothing in the repo making them real. Four Claude
Code subagents (`.claude/agents/*.md`) and two skills
(`.claude/skills/ship-project-task`, `.claude/skills/propose-epics`) were
added so the roles and the procedure are checked into git, not held only in
a session's memory. `CLAUDE.md` and this file are the next layer of that same
idea — making as much of what a session needs to know as possible durable
and discoverable rather than re-derived (or lost) every time.

## Open threads as of this writing

- PayPal and SMTP real credentials: still deferred, intentionally.
- Backlog items from the investor-readiness and epic rounds: check
  `/dashboard/progress` for current status — several were still `todo` when
  this file was written and may be done by the time you're reading it.
- The Site Design Configuration panel (admin-editable logo/colors/hero/stats)
  is the concrete answer to "make the graphic design options configurable" —
  worth checking first if that's the kind of task you've been asked to pick
  up.
