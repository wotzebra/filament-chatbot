<?php

namespace Wotz\FilamentChatbot\Agents\Concerns;

use Illuminate\Support\Facades\Context;
use Stringable;

trait ComposesInstructionsWithContext
{
    public function composeInstructions(Stringable|string $baseInstructions): string
    {
        $base = trim((string) $baseInstructions);
        $context = $this->resolvedChatbotContext();

        if ($context === null || $context === '') {
            return $base;
        }

        return $base === '' ? $context : $base . "\n\n" . $context;
    }

    protected function resolvedChatbotContext(): ?string
    {
        return Context::getHidden('chatbot.context');
    }
}
