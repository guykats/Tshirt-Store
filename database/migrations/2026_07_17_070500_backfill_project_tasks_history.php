<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One-time backfill so the project progress board doesn't start empty: every row
     * here maps to a real merge that already happened, linked to its actual commit SHA
     * rather than a self-reported claim. Work going forward is inserted as it happens
     * instead of needing another migration like this one.
     */
    public function up(): void
    {
        $now = now();

        $tasks = [
            ['title' => 'Database schema (Milestone 1)', 'description' => 'Users, addresses, designs, products, variants, orders, order items.', 'agent_name' => 'Dev Agent', 'task_type' => 'infra', 'commit_sha' => '7ee01de15ab13356fd895edae17638f0bac8e1bc'],
            ['title' => 'Laravel API + React SPA scaffold (Milestone 2)', 'description' => 'Sanctum auth, API resources/controllers, Vite-built React frontend.', 'agent_name' => 'Dev Agent', 'task_type' => 'feature', 'commit_sha' => '78a67300867ac027526ee1c80dc088c0a4295f5f'],
            ['title' => 'CI/CD deploy pipeline', 'description' => 'GitHub Actions workflow building the frontend and deploying to store.guykats.com over SSH.', 'agent_name' => 'Ops Agent', 'task_type' => 'infra', 'commit_sha' => 'ab99480a4142508b5ca5279ec3d46f17d69b8931'],
            ['title' => 'app:create-admin console command', 'description' => 'Interactive command to create an admin without exposing role via mass assignment.', 'agent_name' => 'Dev Agent', 'task_type' => 'feature', 'commit_sha' => 'c1cccac572b74388d190bebce07e71f596715b66'],
            ['title' => 'PayPal checkout integration (Milestone 3)', 'description' => 'Orders v2 REST integration, checkout API, React checkout UI.', 'agent_name' => 'Dev Agent', 'task_type' => 'feature', 'commit_sha' => '3cbe4341c10b636e4e301fe8111a4de2e846567e'],
            ['title' => 'System Control Center dashboard (Milestone 4)', 'description' => 'Human-in-the-loop design/order approval, system events audit trail, agent status board.', 'agent_name' => 'Dev Agent', 'task_type' => 'feature', 'commit_sha' => 'dc5e32bde1db18469bda5ace8befe440c1477d5c', 'screenshot_path' => 'task-screenshots/team-progress-dashboard.png'],
            ['title' => 'PDF invoices + order confirmation emails (Milestone 5)', 'description' => 'Bilingual (en/he) PDF invoice generation, localized order confirmation mail.', 'agent_name' => 'Ops Agent', 'task_type' => 'feature', 'commit_sha' => 'cca09e9025b11a966a9bfc4ef27e2515d6ec5d00'],
            ['title' => 'Brand design system + demo catalog (Phase 2)', 'description' => '9 original SVG cultural-signal motifs, palette/typography system, 7-product demo catalog, product detail page.', 'agent_name' => 'Creative Agent', 'task_type' => 'design', 'commit_sha' => 'fef6473a50eec133c2980aebe0d44763fa6f0e47'],
            ['title' => 'API feature test coverage', 'description' => '33 feature tests across auth, catalog, checkout, and approval flows; found and fixed 2 real bugs along the way.', 'agent_name' => 'Dev Agent', 'task_type' => 'quality', 'commit_sha' => '6bdd8fe967faa863edf8cd70fcc44e3b247875fa'],
            ['title' => 'Fix team progress dashboard staleness', 'description' => 'Agent Status board never got updated after Milestone 4 shipped. Backfilled real task history and added a live git-log "Recent Activity" feed that can\'t go stale.', 'agent_name' => 'Dev Agent', 'task_type' => 'bugfix', 'commit_sha' => '0fcfd936d9a29a8648b65497ba4a29309bb969df', 'screenshot_path' => 'task-screenshots/team-progress-dashboard.png'],
            ['title' => 'Fix CI: build frontend before running tests', 'description' => 'Tests workflow never ran npm run build, so GET / 500\'d on the missing Vite manifest on every run.', 'agent_name' => 'Dev Agent', 'task_type' => 'bugfix', 'commit_sha' => '0d7897c9568f20a2515eaf58a00645e6f230b809'],
            ['title' => 'Per-page SEO meta titles + descriptions', 'description' => 'Every route showed the same generic title. Added a useDocumentMeta hook wired into all pages.', 'agent_name' => 'Creative Agent', 'task_type' => 'feature', 'commit_sha' => 'e42a9848f2d96f0b36e7e2d07a376c2b748ba3f5'],
            ['title' => 'Write project README', 'description' => 'Setup, architecture, env vars, testing, and deployment documentation.', 'agent_name' => 'Ops Agent', 'task_type' => 'docs', 'commit_sha' => 'b8b56f161841ff399b55335a8de392d34abd72bb'],
            ['title' => 'Fix overselling, draft-product purchases, mail-crashing-payment bugs', 'description' => 'Stock was never decremented at checkout; draft/archived products were publicly purchasable; a mail failure could 500 an already-successful payment. Fixed all three with test coverage.', 'agent_name' => 'Dev Agent', 'task_type' => 'security', 'commit_sha' => '2090e2a06963e8e26a16062665da1835706ca623'],
            ['title' => 'Log payment capture to the audit trail', 'description' => 'Order approvals were logged to system_events; the actual payment capture wasn\'t.', 'agent_name' => 'Dev Agent', 'task_type' => 'bugfix', 'commit_sha' => 'eaea8aa8ad62f2b4b4355dd0d7ad5c5451f311a7'],
            ['title' => 'Rate limit login and registration', 'description' => 'Neither endpoint had any throttling, allowing unlimited password-guessing attempts.', 'agent_name' => 'Dev Agent', 'task_type' => 'security', 'commit_sha' => '4f729a1d08598de7b39e6f46675d6fe13b48ea89'],
            ['title' => 'Fix PayPal webhook signature bypass + checkout UX', 'description' => 'Webhook silently skipped signature verification when unconfigured, allowing forged payment events. Also fixed checkout double-submit and a silently-failing payment capture.', 'agent_name' => 'Dev Agent', 'task_type' => 'security', 'commit_sha' => '0bb42c1ea3c36c9798459226d05f0efa53639fef', 'screenshot_path' => 'task-screenshots/checkout-ux-fixes.png'],
            ['title' => 'Deploy maintenance-mode window + order approval idempotency', 'description' => 'Deploys had no maintenance mode and could run migrations against stale cached config. Re-approving an order duplicated the audit log.', 'agent_name' => 'Ops Agent', 'task_type' => 'infra', 'commit_sha' => 'af27a6f5e7abdc92a38ed1d7aa78ac74a6953a98'],
        ];

        foreach ($tasks as $task) {
            DB::table('project_tasks')->insert(array_merge([
                'description' => null,
                'commit_sha' => null,
                'screenshot_path' => null,
                'blocked_reason' => null,
            ], $task, [
                'status' => 'done',
                'completed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        DB::table('project_tasks')->insert([
            'title' => 'Build project progress dashboard',
            'description' => 'A Jira-style board tracking what each agent worked on, its status, and (for UI work) a screenshot of the result — separate from the site-operations dashboard.',
            'agent_name' => 'Dev Agent',
            'status' => 'in_progress',
            'task_type' => 'feature',
            'commit_sha' => null,
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->truncate();
    }
};
