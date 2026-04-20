<?php

namespace Wotz\FilamentChatbot\Support\Chatbot;

use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

class FriendlyErrorMessage
{
    public static function resolve(Throwable $e): string
    {
        return match (true) {
            $e instanceof RateLimitedException => __('filament-chatbot::chatbot.errors.rate_limited'),
            $e instanceof ProviderOverloadedException => __('filament-chatbot::chatbot.errors.overloaded'),
            $e instanceof InsufficientCreditsException => __('filament-chatbot::chatbot.errors.insufficient_credits'),
            $e instanceof AiException => __('filament-chatbot::chatbot.errors.ai_failed'),
            default => __('filament-chatbot::chatbot.errors.generic'),
        };
    }
}
