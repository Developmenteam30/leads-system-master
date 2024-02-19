<?php

namespace App\Responses;

class ErrorResponse
{
    public static function json(string $message, int $status)
    {
        return response()->json(['message' => $message], $status);
    }
}
