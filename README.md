# Task-ms (Advanced Laravel API Architecture with Laravel 12, Sanctum and Redis)

[![PHP Version](https://img.shields.io/badge/php-%3E=8.1-8892BF.svg)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-12.x-FF2D20.svg)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

This project establishes a highly scalable, secure, and performant API platform. It is engineered around clear **Separation of Concerns (SoC)**, employing a robust **Layered Architecture** (Presentation, Business, Data) to maximize maintainability, ease of testing, and operational efficiency.

## Architectural Principles & Design Decisions

The design prioritizes three core objectives: data security, high read performance via caching, and code clarity through abstraction.

### 1. Data Layer Security and Abstraction

The Model Layer is fortified to ensure access control is an inherent property of the data itself.

| Component | Rationale & Implementation | Architectural Impact |
| :--- | :--- | :--- |
| **Global Ownership Scope (`UserOwnershipScope`)** | **Decision:** To enforce Row-Level Security (RLS). The scope is registered within the `Task` model's `booted()` method, automatically injecting `WHERE user_id = Auth::id()` into all queries. | **Security Assurance:** Eliminates the risk of horizontal privilege escalation, ensuring users only access their own data without relying on controller-level checks. |
| **Custom Query Builder (`TaskBuilder`)** | **Decision:** To abstract complex data retrieval logic. The `TaskBuilder` centralizes filtering (e.g., `filter()`, `search()`) logic, adhering to the **Repository Pattern** principle. | **Code Quality:** Provides a clean, fluent interface (`Task::query()->filter(...)`) for data access, decoupling data query specifics from the Service Layer. |
| **Factories and Seeding** | **Decision:** Implement dedicated factories (`TaskFactory`) and seeders (`UserSeeder`) to populate the database with controlled, high-fidelity test data. **`UserSeeder`** uses `hasTasks(rand(3, 7))` to ensure every test user has a unique set of owned tasks. | **Testing Integrity:** Guarantees reliable data ownership scenarios for robust testing of security scopes and policies. |

### 2. Business Layer Performance & Consistency

The Service Layer manages business logic and employs an advanced caching strategy to optimize read performance while guaranteeing data consistency.

#### Tag-Driven Cache Invalidation
The strategy utilizes Redis to manage caches via tags, ensuring immediate consistency across the application state after any mutation.

* **Cache Strategy**: The `TaskService` generates unique cache keys by hashing all request parameters (`filters`, `pagination`, and crucially, the **`Auth::id()`**). Data is stored with two tags: a global tag (`tasks`) and a specific tag (`task_{id}`).
* **Automatic Invalidation**: The `AutoFlushCache` trait, linked to the `Task` model's lifecycle events (`saved`, `deleted`), automatically flushes these tags:
    * **Single-Item Flush**: `task_{id}` tag is cleared.
    * **List Flush (Ripple Effect)**: The global `tasks` tag is cleared, forcing subsequent list requests to rebuild their cache. 

#### Optimized Retrieval Path

* **Decision**: `TaskController@show` accepts an `int $id` instead of using Route Model Binding (`Task $task`).
* **Rationale**: This forces the request through `TaskService::getById($id)`, enabling a **Cache-First** approach. The service checks Redis; if a hit occurs, **zero database queries are executed**, significantly boosting the performance of read operations.

### 3. Presentation Layer (API Endpoints)

The Controllers are minimalist, acting primarily as marshals that validate input, enforce policies, and delegate tasks to the Service Layer.

#### Strict Authorization (Policies)

The `TaskPolicy` strictly controls access. Authorization checks (`$this->authorize(...)`) are implemented in every controller method, ensuring that only users who pass the policy check can proceed with the operation.

* **`viewAny` & `create`**: Checked against the class (`Task::class`).
* **`view`, `update`, `delete`**: Checked against the model instance (`$task`) for ownership verification.

#### API Resource Endpoints

| Endpoint | HTTP Method | Data Flow & Security Constraints | Policy Check |
| :--- | :--- | :--- | :--- |
| `/v1/register` | `POST` | New User Creation (`AuthService`). Throttled. | N/A |
| `/v1/login` | `POST` | Token Issuance (`AuthService`). Throttled. | N/A |
| `/v1/tasks` | `GET` | Paginated and filtered list. Query execution leverages `TaskBuilder` and is restricted by `UserOwnershipScope`. | `viewAny` |
| `/v1/tasks/{id}` | `GET` | Cache-First retrieval via `TaskService`. | `view` (Ownership check) |
| `/v1/tasks/{id}` | `PUT/PATCH`| Update operation via `TaskService`. Triggers automatic cache invalidation. | `update` (Ownership check) |

## Deployment and Execution

### Prerequisites
* PHP 8.1+
* Composer
* Relational Database (e.g., MySQL 8+)
* Redis (Required for the `CACHE_STORE=redis` implementation)

### Setup Instructions

1.  **Clone & Install Dependencies:**

    ```bash
    git clone https://github.com/BsHeR4/task-ms.git
    composer install
    ```

2.  **Environment Configuration:**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

    Verify the following configuration in your `.env` for production readiness:

    ```env
    # Ensure Redis is configured for tagged caching functionality
    CACHE_STORE=redis 
    REDIS_HOST=127.0.0.1 
    
    # Database connection details
    DB_CONNECTION=mysql 
    ```

3.  **Database Migration and Seeding:**

    Run the migrations and then execute the seeder to populate the database with the predefined test accounts and their tasks.

    ```bash
    php artisan migrate --seed
    ```

4.  **Run Development Server:**

    ```bash
    php artisan serve
    ```

5.  **Interact with the API**:
    Use Postman to test the various endpoints. Get the collection from [here](https://documenter.getpostman.com/view/33882685/2sB3dSP8dD).
---

## Testing Credentials

The `UserSeeder` has created two dedicated test accounts, each dynamically assigned 3 to 7 unique tasks. These accounts are essential for testing the full lifecycle of the **Task Management Module** and verifying the strict security constraints enforced by the **`UserOwnershipScope`**.

| User | Email | Password |
| :--- | :--- | :--- |
| **bsher** | `bsher@gmail.com` | `passWord@12` |
| **mohammed** | `mohammed@gmail.com` | `passWord@12` |

**Testing Recommendation:**
1.  Log in as `bsher` to obtain an API Token.
2.  Use this Token to fetch tasks (GET `/v1/tasks`).
3.  Attempt to fetch a specific task ID that belongs to `mohammed`. The API should return a `404 Not Found` or `403 Forbidden` error due to the combined enforcement of the **`UserOwnershipScope`** and **`TaskPolicy`**.

## API Response Handling: Architectural Note

While Laravel Resource Collections were specified as a requirement, I opted for a customized, centralized response pattern using a **Base Controller** with helper methods (`successResponse`, `errorResponse`, `paginate`, etc.).

**Reasoning for this alternative approach:**

1.  **Performance Optimization:** In systems focused on high throughput, introducing an additional layer of abstraction (Resources) can occasionally contribute to minor performance overhead. By handling data formatting directly through controlled helper methods, the response generation is streamlined.
2. **Consistency and Simplicity:** This approach guarantees a unified and predictable JSON structure for all API endpoints, including standardized error handling and comprehensive pagination metadata, which simplifies consumption by the ReactJS frontend.

#### The custom helper methods serve the same core purpose as Resource Collections — ensuring data consistency and separation of concerns — but within a more direct response structure.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).