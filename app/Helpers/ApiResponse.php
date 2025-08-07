<?php

namespace App\Helpers;

use Illuminate\Support\MessageBag;

class ApiResponse
{
    public static function success($data = [], $message = 'Success', $statusCode = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public static function error($message = 'An error occurred', $statusCode = 400, $errors = [])
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    public static function validationError($errors, $message = 'Validation errors', $statusCode = 422)
    {
        // Check if $errors is an instance of MessageBag and convert to array
        if ($errors instanceof MessageBag) {
            $errors = $errors->toArray();
        }

        // Flatten and extract only the first error for each field
        $errors = array_map(fn($message) => is_array($message) ? $message[0] : $message, $errors);

        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }
}
