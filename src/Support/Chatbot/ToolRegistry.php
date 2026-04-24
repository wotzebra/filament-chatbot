<?php

namespace Wotz\FilamentChatbot\Support\Chatbot;

use Laravel\Ai\Contracts\Tool;
use RuntimeException;

class ToolRegistry
{
    /**
     * @param  array<int, mixed>  $extraTools
     * @return array<int, Tool>
     */
    public function resolveTools(array $extraTools = []): array
    {
        $configTools = config('filament-chatbot.tools', []);

        return collect([...$configTools, ...$extraTools])
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
