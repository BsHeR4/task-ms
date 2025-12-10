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

    /**
     * Standardized paginate response.
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginateData
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected static function paginate($paginateData, $message = 'Data retrieved successfully')
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $paginateData->items(),
            'meta' => [
                'current_page' => $paginateData->currentPage(),
                'per_page' => $paginateData->perPage(),
                'total' => $paginateData->total(),
                'last_page' => $paginateData->lastPage()
            ]
        ], 200);
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
            'message' => 'Registered successfully',
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
