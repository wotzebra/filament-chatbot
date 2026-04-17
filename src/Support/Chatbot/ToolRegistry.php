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

    public function usingTools(array $toolClasses, callable $callback): mixed
    {
        $previousTools = $this->extraTools;
        $this->extraTools = $toolClasses;

        try {
            return $callback();
        } finally {
            $this->extraTools = $previousTools;
        }
    }

    public function resolveTools(): array
    {
        $configTools = config('filament-chatbot.tools', []);

        return collect([...$configTools, ...$this->extraTools])
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
