<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class ActivityController extends Controller
{
    /**
     * Recent git commits, read live from the server on every request. Unlike the
     * agent_statuses board (which only reflects reality if someone remembers to update it),
     * this can never go stale — it's just what actually got deployed.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $result = Process::path(base_path())->run([
            'git', 'log', '-20', '--pretty=format:%H|%an|%aI|%s',
        ]);

        if (! $result->successful()) {
            return response()->json(['data' => []]);
        }

        $commits = collect(explode("\n", trim($result->output())))
            ->filter()
            ->map(function (string $line) {
                [$hash, $author, $date, $message] = array_pad(explode('|', $line, 4), 4, null);

                return [
                    'hash' => substr((string) $hash, 0, 7),
                    'author' => $author,
                    'date' => $date,
                    'message' => $message,
                ];
            })
            ->values();

        return response()->json(['data' => $commits]);
    }
}
