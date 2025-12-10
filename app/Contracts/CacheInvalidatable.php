<?php

namespace App\Contracts;

interface CacheInvalidatable
{
    /**
     * Return the list of cache tags that should be flushed when this model is modified.
     * This includes the model's own tag AND related models' tags (Ripple Effect).
     *
     * @return array<string>
     */
    public function getCacheTagsToInvalidate(): array;
}

