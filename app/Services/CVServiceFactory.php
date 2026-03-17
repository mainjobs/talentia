<?php

namespace App\Services;

use App\Services\GeminiCVService;
use App\Services\OpenAICVService;

class CVServiceFactory
{
    public static function make(string $model): GeminiCVService|OpenAICVService
    {
        return match(true) {
            str_starts_with($model, 'gemini') => app(GeminiCVService::class),
            default                           => app(OpenAICVService::class),
        };
    }
}