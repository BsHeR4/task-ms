<?php

namespace App\Models;

use App\Contracts\CacheInvalidatable;
use App\Enums\TaskStatus;
use App\Models\Builders\TaskBuilder;
use App\Models\Scopes\UserOwnershipScope;
use App\Traits\AutoFlushCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model implements CacheInvalidatable
{
    use AutoFlushCache;

    protected $fillable = [
        'title',
        'description',
        'status',
        'user_id',
    ];

    protected $casts = [
        'status' => TaskStatus::class
    ];

    /**
     * Define cache tags to invalidate.
     * Here we handle the "Ripple Effect".
     */
    public function getCacheTagsToInvalidate(): array
    {
        return [
            'tasks',
            "task_{$this->id}"
        ];
    }

    /**
     * The "booted" method of the model.
     *
     * This is the standard place to register Global Scopes or Model Observers.
     *
     * @return void
     */
    protected static function booted()
    {
        // Register the UserOwnershipScope.
        // Effect: All queries (Task::all(), Task::where...) will automatically
        // get user's records unless disabled manually.
        static::addGlobalScope(new UserOwnershipScope);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * By overriding this method, we tell Laravel to use our custom `TaskBuilder`
     * instead of the default `Illuminate\Database\Eloquent\Builder`.
     * This enables intellisense for methods like `Task::query()->search()`.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return TaskBuilder
     */
    public function newEloquentBuilder($query): TaskBuilder
    {
        return new TaskBuilder($query);
    }

    /**
     * Get the user that this task belongs to.
     *
     * Relationship: Many-to-One.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
