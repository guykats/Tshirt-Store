<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Namespaces catalog read caching behind an incrementing version rather than
 * cache tags, since the default 'database' cache store doesn't support tags.
 * Bumping the version on any product/variant write invalidates every prior
 * catalog cache entry at once without having to enumerate and delete keys.
 */
class CatalogCache
{
    private const VERSION_KEY = 'catalog:cache-version';

    private const TTL_SECONDS = 300;

    public static function remember(string $key, Closure $callback): mixed
    {
        return Cache::remember(self::namespacedKey($key), self::TTL_SECONDS, $callback);
    }

    public static function flush(): void
    {
        Cache::add(self::VERSION_KEY, 1, now()->addYears(10));
        Cache::increment(self::VERSION_KEY);
    }

    private static function namespacedKey(string $key): string
    {
        $version = Cache::get(self::VERSION_KEY, 1);

        return "catalog:v{$version}:{$key}";
    }
}
