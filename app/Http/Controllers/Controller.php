<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Generate a standard API response
     *
     * @param bool $success Indicates if the response is successful
     * @param string $message The message to include in the response
     * @param $data The data to include in the response (optional)
     * @param int $statusCode The HTTP status code (default is 200)
     * @return JsonResponse The JSON response
     */
    protected function apiResponse($success, $message, $data = null, $statusCode = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Generate a success response
     *
     * @param string $message The success message
     * @param $data The data to include in the response (optional)
     * @param int $statusCode The HTTP status code (default is 200)
     * @return JsonResponse The JSON success response
     */
    protected function successResponse($message, $data = null, $statusCode = 200)
    {
        return $this->apiResponse(true, $message, $data, $statusCode);
    }

    /**
     * Generate an error response
     *
     * @param string $message The error message
     * @param $data The data to include in the response (optional)
     * @param int $statusCode The HTTP status code (default is 400)
     * @return JsonResponse The JSON error response
     */
    protected function errorResponse($message, $data = null, $statusCode = 400)
    {
        return $this->apiResponse(false, $message, $data, $statusCode);
    }

    //-------------------------------- AUTH --------------------------------//

    /**
     * Generate a login response
     *
     * @param $data The user data to include in the response
     * @param string $token The authentication token
     * @return JsonResponse The JSON login response
     */
    protected function loginResponse($data, $token)
    {
        return response()->json([
            'status' => true,
            'message' => 'Login Successfully',
            'data' => $data,
            'token' => $token,
            'code' => 200,
        ]);
    }

    /**
     * Generate a registration response
     *
     * @param $data The user data to include in the response
     * @return JsonResponse The JSON registration response
     */
    protected function registerResponse($data)
    {
        return response()->json([
            'status' => true,
            'message' => 'Register successfully, verification link have been sent to your email',
            'data' => $data,
            'code' => 201,
        ], 201);
    }

    /**
     * Generate a logout response
     *
     * @return \Illuminate\Http\JsonResponse The logout success message with a 204 No Content status.
     */
    protected function logoutResponse()
    {
        return response()->json([
            'message' => "Logged out successfully"
        ], 204);
    }
}
