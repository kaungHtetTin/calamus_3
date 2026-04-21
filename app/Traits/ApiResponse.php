<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Send a success response.
     *
     * @param mixed $data
     * @param int $statusCode
     * @param array|null $meta
     * @return JsonResponse
     */
    protected function successResponse($data, $statusCode = 200, $meta = null): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $this->camelizeKeys($data),
        ];

        if ($meta !== null) {
            $response['meta'] = $this->camelizeKeys($meta);
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Helper to build pagination meta.
     */
    protected function paginate($total, $page, $limit)
    {
        $lastPage = (int)ceil($total / $limit);
        return [
            'currentPage' => (int)$page,
            'lastPage' => $lastPage,
            'nextPageToken' => ($page < $lastPage) ? (string)($page + 1) : null,
            'total' => (int)$total,
            'limit' => (int)$limit,
        ];
    }

    /**
     * Recursively convert array keys to camelCase.
     */
    protected function camelizeKeys($data)
    {
        if (!is_array($data) && !is_object($data)) {
            return $data;
        }

        // Convert object to array if it's an Eloquent model or collection
        if ($data instanceof \Illuminate\Support\Collection || $data instanceof \Illuminate\Database\Eloquent\Model) {
            $data = $data->toArray();
        } elseif (is_object($data)) {
            $data = (array)$data;
        }

        $result = [];
        foreach ($data as $key => $value) {
            // Convert key to camelCase (skip numeric keys)
            $camelKey = is_numeric($key) ? $key : \Illuminate\Support\Str::camel($key);
            
            // Recursively convert value if it's an array or object
            if (is_array($value) || is_object($value)) {
                $result[$camelKey] = $this->camelizeKeys($value);
            } else {
                $result[$camelKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Send an error response.
     *
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function errorResponse($message, $statusCode): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
        ], $statusCode);
    }
}
