<?php

namespace Wotz\FilamentChatbot\Support\Chatbot;

use Laravel\Ai\Contracts\Tool;
use RuntimeException;

class ToolRegistry
{
    protected array $extraTools = [];

    public function withTools(array $toolClasses): static
    {
        $this->extraTools = $toolClasses;

        return $this;
    }

    public function resolveTools(): array
    {
        return collect(config('filament-chatbot.tools', config('filament-chatbot.agent.tools', [])))
            ->merge($this->extraTools)
            ->map(fn (mixed $tool) => is_object($tool) ? $tool : app($tool))
            ->each(function (mixed $tool): void {
                if (! $tool instanceof Tool) {
                    throw new RuntimeException(sprintf(
                        'Chatbot tool [%s] must implement %s.',
                        $tool::class,
                        Tool::class,
                    ));
                }
            })
            ->unique(fn (Tool $tool) => $tool::class)
            ->values()
            ->all();
    }
}
