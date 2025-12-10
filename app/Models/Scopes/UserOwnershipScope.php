<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global Scope to enforce dynamic User Ownership constraints on models.
 *
 * This scope restricts query results to only include records owned by the
 * currently authenticated user.
 *
 * Logic:
 *  - The column used for ownership is determined dynamically:
 *      1. The scope first checks if the model instance implements a method named `getOwnerKeyName()`.
 *      2. If the method exists, its returned value (e.g., 'author_id') is used as the key name.
 *      3. If the method does NOT exist, the scope defaults to the Laravel convention: 'user_id'.
 *
 * To use a custom column (e.g., 'creator_id', 'author_id'), simply define the method in your model:
 *
 * ```
 * // Example Model implementation:
 * public function getOwnerKeyName(): string
 * {
 *      return 'creator_id';
 * }
 * ```
 *
 * @implements Scope
 */
class UserOwnershipScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Injects a WHERE clause, using the owner key name dynamically retrieved from the model.
     *
     * @param  Builder  $builder  The current Eloquent query builder instance.
     * @param  Model  $model      The model instance being queried.
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        // 1. Determine the name of the ownership column dynamically.
        // Check if the model has defined a custom method to override the default key.
        if (method_exists($model, 'getOwnerKeyName')) {
            // Use the custom key name defined by the model (e.g., 'author_id').
            $ownerKeyName = $model->getOwnerKeyName();
        } else {
            // Fallback to the standard Laravel convention ('user_id') if no custom method is found.
            $ownerKeyName = 'user_id';
        }

        // 2. Apply the ownership constraint to the query.
        // WHERE [dynamic ownerKeyName] = currently authenticated user ID
        $builder->where($ownerKeyName, '=', Auth::id());
    }
}
