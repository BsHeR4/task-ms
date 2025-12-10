<?php

namespace App\Traits;

use App\Contracts\CacheInvalidatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Trait AutoFlushCache
 *
 * Automatically flushes cache tags when the model is saved or deleted.
 * Requires the model to implement CacheInvalidatable.
 */
trait AutoFlushCache
{
    public static function bootAutoFlushCache(): void
    {
        static::saved(function (Model $model) {
            self::invalidate($model);
        });

        static::deleted(function (Model $model) {
            self::invalidate($model);
        });
    }

    private static function invalidate(Model $model): void
    {
        if (! $model instanceof CacheInvalidatable) {
            return;
        }

        $tags = $model->getCacheTagsToInvalidate();

        if (! empty($tags)) {
            Cache::tags($tags)->flush();
        }
    }
}
