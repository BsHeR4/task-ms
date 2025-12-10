<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     * @param  User  $user  The authenticated user.
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Public Access: Any authenticated user can browse their own tasks list.
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * @param  User    $user
     * @param  Task  $task
     * @return bool
     */
    public function view(User $user, Task $task): bool
    {
        // Check if the User ID on the task matches the current User ID.
        return $user->id == $task->user_id;
    }

    /**
     * Determine whether the user can create models.
     * 
     * Logic:
     * Any authenticated user in our system allowed to initiate a task.
     * 
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * 
     * Logic:
     * - Users can cancel their own tasks
     *
     * @param  User    $user
     * @param  Task  $task
     * @return bool
     */
    public function update(User $user, Task $task): bool
    {
        // Check if the User ID on the task matches the current User ID.
        return $user->id == $task->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     * 
     * Logic:
     * Users can delete their own tasks
     *
     * @param  User    $user
     * @param  Task  $ticket
     * @return bool
     */
    public function delete(User $user, Task $task): bool
    {
        // Check if the User ID on the task matches the current User ID.
        return $user->id == $task->user_id;
    }
}
