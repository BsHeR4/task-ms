<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Service class for managing Tasks.
 *
 * This class isolates the business logic for Tasks, handling:
 * 1. Database Interactions (CRUD).
 */
class TaskService
{

    /**
     * Cache Time-To-Live: 1 Hour (in seconds).
     */
    private const CACHE_TTL = 3600;

    /**
     * Centralized Cache Tags.
     * Defined as constants to prevent hardcoded string typos.
     */
    private const TAG_TASKS_GLOBAL = 'tasks';     // Tag for lists of tasks
    private const TAG_TASK_PREFIX = 'task_';      // Tag for specific task details

    /**
     * List Tasks with a robust Caching Strategy.
     *
     * The results are automatically scoped by User Ownership via the registered
     * Task model's Global Scope (UserOwnershipScope).
     *
     * Logic:
     * - Generates a unique cache signature based on filters, pagination and authenticated user ID.
     * - Uses `ksort` to ensure parameter order doesn't affect the cache hit rate.
     *
     * @param array<string, mixed> $filters Filters from the HTTP request (e.g., ['search' => 'test', 'status' => 'pending']).
     * @param int $perPage Items per page.
     * @return LengthAwarePaginator
     */
    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        // 1. Normalize Filters:
        // We sort by key so that requests like ?status=done&page=1
        // and ?page=1&status=done generate the SAME cache key.
        ksort($filters);

        // 2. Pagination State:
        // Retrieve the current page number from the request to include it in the cache key.
        $page = (int) request('page', 1);

        //Add the authenticated user ID to the parameters being hashed
        $userId = Auth::id();

        // 3. Unique Cache Key Generation:
        // Hash the serialized parameters to create a safe, short, and unique cache key.
        $cacheBase = json_encode($filters) . "_limit_{$perPage}_page_{$page}_user_{$userId}";

        $cacheKey = 'tasks_list_' . md5($cacheBase);

        // 4. Cache Retrieval:
        // We use the global 'tasks' tag to allow mass invalidation when any task is updated.
        return Cache::tags([self::TAG_TASKS_GLOBAL])->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($filters, $perPage) {
                // The Task::query() call implicitly includes the UserOwnershipScope.
                return Task::query()
                    ->filter($filters)      // Apply custom TaskBuilder logic (search, status, etc.)
                    ->paginate($perPage);   // Pagination handles the limit and offset.
            }
        );
    }

    /**
     * Get Single Task details with Caching.
     *
     * Workflow:
     * - Caches the specific task data using two tags:
     * 1. Global Tasks Tag (to allow mass clearing).
     * 2. Specific Task Tag (to allow clearing just this record).
     *
     * The findOrFail method respects the UserOwnershipScope, automatically throwing
     * a ModelNotFoundException if the task exists but does not belong to the user.
     *
     * @param int $id Task ID.
     * @return Task
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getById(int $id): Task
    {
        $cacheKey = self::TAG_TASK_PREFIX . "details_{$id}";
        $taskTag = self::TAG_TASK_PREFIX . $id;

        return Cache::tags([self::TAG_TASKS_GLOBAL, $taskTag])->remember(
            $cacheKey,
            self::CACHE_TTL,
            // The findOrFail call is automatically scoped by the UserOwnershipScope.
            fn() => Task::findOrFail($id)
        );
    }


    /**
     * Create a new Task.
     *
     * @param array $data Validated data.
     * @return Task
     */
    public function create(array $data): Task
    {
        //add the authenticated user to the validated data array ($data)
        $data['user_id'] = auth()->id();

        $task = Task::create($data);

        return $task;
    }

    /**
     * Update an existing Task.
     *
     * @param Task $task
     * @param array $data
     * @return Task
     */
    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->refresh();
    }

    /**
     * Delete a Task.
     *
     * @param Task $task
     * @return void
     */
    public function delete(Task $task): void
    {
        $task->delete();
    }
}
