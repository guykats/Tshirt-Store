<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Directory
    |--------------------------------------------------------------------------
    |
    | Absolute path that mysqldump output is written to. This deliberately
    | defaults to a sibling directory of the app root (one level above
    | base_path(), i.e. outside the git working tree that deploy.yml runs
    | `git reset --hard` against) so a deploy can never wipe out a backup —
    | reset --hard only ever touches files inside the repo it's resetting.
    |
    */

    'path' => env('DB_BACKUP_PATH', dirname(base_path()).'/db-backups'),

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Number of most-recent daily dumps to keep. Anything older is pruned
    | after each successful backup run.
    |
    */

    'keep' => (int) env('DB_BACKUP_KEEP', 14),

    /*
    |--------------------------------------------------------------------------
    | Off-site Backup Disk
    |--------------------------------------------------------------------------
    |
    | Name of a filesystem disk (see config/filesystems.php) that a copy of
    | each successful local dump is also uploaded to, so a full loss of the
    | Hostinger box doesn't take every backup down with it. Reuses the
    | existing 's3' disk, which already reads its credentials from env
    | (AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY / etc.) — those credentials
    | are deferred for now (see CLAUDE.md's standing rule on PayPal/SMTP
    | secrets), so this defaults to null/unset and off-site upload is a
    | clean no-op until real env vars are added in production, at which
    | point it activates automatically with no code change.
    |
    */

    'offsite_disk' => env('BACKUP_OFFSITE_DISK'),

];
