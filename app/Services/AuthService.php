<?php

namespace App\Services;

use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Service class for handling all user authentication logic
 *
 * This service manages user registration, login, and logout operations
 * It uses a central `handle` method to ensure consistent error handling
 * and logging for all auth-related actions
 */
class AuthService
{
    /**
     * A central error handler for authentication operations
     *
     * This wrapper provides a consistent try/catch block for auth methods,
     * so we don't have to repeat the same error handling logic everywhere
     * It catches specific exceptions and re-throws them in a standard format
     *
     * @param Closure $callback The function containing the core logic to execute
     * @return mixed The result from the callback function
     * @throws \Illuminate\Auth\AuthenticationException If credentials are bad
     * @throws \Exception For any other unexpected errors
     */
    protected function handle(Closure $callback)
    {
        try {
            // Run the logic passed into the handler
            return $callback();
        } catch (AuthenticationException $e) {
            // Catch a specific framework auth exception
            report($e);
            // Re-throw it with a generic, user-friendly message
            throw new AuthenticationException('Invalid credentials');
        } catch (\Throwable $e) {
            // Catch any other possible error to prevent crashes
            report($e);
            // Re-throw it as a generic server error
            throw new \Exception('An internal server error occurred', 500);
        }
    }

    /**
     * Handle a new user registration
     *
     * Hashes the password, creates the user, generates an API token,
     *
     * @param array $data User data (name, email, password)
     * @return array|bool An array with the user's name and token on success, or false on failure
     */
    public function register(array $data)
    {
        return $this->handle(function () use ($data) {
            // hash the password before saving it
            $data['password'] = Hash::make($data['password']);

            $user = User::create($data);

            // Make sure the user was actually created before proceeding
            if ($user->wasRecentlyCreated) {
                // Prepare the success response
                $success['name'] = $user->name;
                // Create a Sanctum token that expires in one week
                $success['token'] = $user->createToken('MyApp', ['*'], now()->addWeek())->plainTextToken;

                return $success;
            }

            // Return false if for some reason the user wasn't created
            return false;
        });
    }

    /**
     * Handle a user login attempt
     *
     * Tries to authenticate the user with email and password
     * If successful, it creates a new API token
     *
     * @param array $data User credentials (email, password)
     * @return array|bool An array with the user object and token, or false on failure
     */
    public function login(array $data)
    {
        return $this->handle(function () use ($data) {
            // Attempt to authenticate
            if (Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
                $user = Auth::user();
                $token = $user->createToken('MyApp', ['*'], now()->addWeek())->plainTextToken;

                return [
                    'user' => $user,
                    'token' => $token,
                ];
            }

            return false;
        });
    }

    /**
     * Log the current user out by revoking their API token
     *
     * @return bool True if the token was deleted successfully
     */
    public function logout()
    {
        return $this->handle(function () {
            $user = auth()->user();

            // Delete the specific token that was used for the current request
            return $user->currentAccessToken()->delete();
        });
    }
}
