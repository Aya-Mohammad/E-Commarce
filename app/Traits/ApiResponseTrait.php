<?php

namespace App\Traits;

trait ApiResponseTrait
{
    public function apiResponse($data = null, $message = null, $status = 200, $errors = [])
    {
        return response()->json([
            'success' => $status >= 200 && $status < 300,
            'data' => $data,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
