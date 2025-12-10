<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Task\StoreTaskRequest;
use App\Http\Requests\Api\V1\Task\UpdateTaskRequest;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class TaskController extends Controller
{
    use AuthorizesRequests;

    /**
     * Service to handle task-related logic 
     * and separating it from the controller
     * 
     * @var TaskService
     */
    protected $taskService;

    /**
     * AuthController constructor
     *
     * @param TaskService $taskService
     */
    public function __construct(TaskService $taskService)
    {
        // Inject the TaskService to handle task-related logic
        $this->taskService = $taskService;
    }

    /**
     * List tasks with Security Filtering.
     *
     * Workflow:
     * 1. Check general permission to view tickets.
     * 2. **Enforce Data Ownership:** If the user is NOT an admin, we forcibly overwrite
     *    the `user_id` filter to match their ID. This prevents User A from requesting
     *    User B's tickets via API parameters.
     * 3. Delegate to Service for caching and pagination.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        // 1. Authorize: Check Policy::viewAny
        $this->authorize('viewAny', Task::class);

        // Get raw filters from the request (e.g., ?status=done)
        $filters = $request->all();

        // 2. Service: Fetch data (Cached)
        $tasks = $this->taskService->list($filters);

        return $this->paginate($tasks, 'Tasks retrieved successfully');
    }

    /**
     * Store a newly created task in storage.
     * 
     * 1. Validation happens automatically via StoreTaskRequest.
     * 2. Authorize creation.
     * 3. Service creates the record and clears the "Tasks List" cache.
     *
     * @param StoreTaskRequest $request Validated data.
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(StoreTaskRequest $request)
    {
        // 1. Authorize
        $this->authorize('create', Task::class);

        // 2. Service Logic
        $task = $this->taskService->create($request->validated());

        // 3. Response (HTTP 201 Created)
        return $this->successResponse('Task created successfully', $task, 201);
    }
    /**
     * Display the specified speaker.
     *
     * ARCHITECTURAL NOTE:
     * We use `int $id` here instead of Route Model Binding (`Task $task`).
     *
     * Why?
     * If we injected `Task $task`, Laravel would execute a DB Query immediately
     * to find the model. This defeats the purpose of our Service Cache.
     *
     * By passing the ID, we let `$this->taskService->getById($id)` check the
     * Cache (Redis/File) first. If found, NO database query runs.
     *
     * @param int $id The ID of the task.
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(int $id)
    {
        // 1. Retrieve Data (Cache Hit or DB Query via Service)
        $task = $this->taskService->getById($id);

        // 2. Authorize (Now that we have the object, we check permissions)
        $this->authorize('view', $task);

        // 3. Response
        return $this->successResponse('Task retrieved successfully', $task);
    }

    /**
     * Update the specified task in storage.
     *
     * Note: Here we DO use Model Binding (`Task $speaker`) because we are
     * modifying an existing record. We need the instance loaded to verify
     * it exists before attempting an update.
     *
     * @param UpdateTaskRequest $request Validated data.
     * @param Task $task Injected model instance.
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        // 1. Authorize: Check Policy::update
        $this->authorize('update', $task);

        // 2. Service Logic: Updates DB and invalidates specific cache tags.
        $updatedTask = $this->taskService->update($task, $request->validated());

        // 3. Response
        return $this->successResponse('Task updated successfully', $updatedTask);
    }

    /**
     * Delete ticket permanently.
     *
     * @param Task $task Injected model instance.
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Task $task)
    {
        // 1. Authorize (Policy::delete)
        $this->authorize('delete', $task);

        // 2. Service Logic
        $this->taskService->delete($task);

        // 3. Response
        return $this->successResponse('Task deleted successfully');
    }
}
