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

];
