<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * Custom Query Builder for the Task Model.
 *
 * This class handles all database search and filter logic for tasks.
 * By decoupling this logic from the Controller, we make the code reusable
 * and easier to test.
 *
 * @extends Builder<\App\Models\Task>
 */
class TaskBuilder extends Builder
{

    /**
     * Scope to search tasks by title (partial match).
     *
     * Applies a case-insensitive LIKE comparison on the 'title' column.
     *
     * @param string $term The search keyword or phrase.
     * @return self
     */
    public function search(string $term): self
    {
        // Use ILIKE for case-insensitivity if database supports it (PostgreSQL),
        // otherwise, LIKE is used, which is generally case-insensitive in MySQL by default.
        return $this->where('title', 'LIKE', "%{$term}%");
    }

    /**
     * Apply dynamic filters from an HTTP request array.
     *
     * This method provides a clean, fluent interface to conditionally apply
     * various search parameters from a user request (e.g., query string).
     *
     * @param array<string, mixed> $filters Associative array containing filter keys (e.g., `['search' => 'keyword', 'status' => 'done']`).
     * @return self
     */
    public function filter(array $filters): self
    {
        return $this
            // ---------------------------------------------------
            // 1. Title Search Filter
            // ---------------------------------------------------
            // Applies the dedicated `search()` scope if the 'search' key is present.
            ->when(
                $filters['search'] ?? null,
                // Cast to (string) to guarantee type safety before passing to the search method.
                fn($q, $val) => $q->search((string) $val)
            )

            // ---------------------------------------------------
            // 2. Exact Status Filter (ENUM Match)
            // ---------------------------------------------------
            // Filters tasks where the 'status' column exactly matches the provided value.
            ->when(
                $filters['status'] ?? null,
                // Cast to (string) as the status column holds string/enum values.
                fn($q, $val) => $q->where('status', (string) $val)
            );
    }
}
