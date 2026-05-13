<?php

declare(strict_types=1);

namespace App\Services;

use Anthropic\Client;
use RuntimeException;

class AnthropicClientFactory
{
    public static function make(): Client
    {
        $key = config('anthropic.api_key');

        if (empty($key) || str_starts_with($key, 'sk-ant-xxxxx')) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured. Please set a valid API key in .env');
        }

        return new Client(apiKey: $key);
    }

    public static function model(): string
    {
        return config('anthropic.model', 'claude-sonnet-4-20250514');
    }
}
