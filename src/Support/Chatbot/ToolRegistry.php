<?php

namespace Wotz\FilamentChatbot\Support\Chatbot;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\McpServerTool;
use RuntimeException;

class ToolRegistry
{
    /**
     * Resolve the configured tools: Laravel AI tools, or MCP server tools
     * (`Laravel\Mcp\Server\Tool`) when the installed Laravel AI version wraps them.
     *
     * @param  array<int, mixed>  $extraTools
     * @return array<int, object>
     */
    public function resolveTools(array $extraTools = []): array
    {
        $configTools = config('filament-chatbot.tools', []);

        return collect([...$configTools, ...$extraTools])
            ->map(fn (mixed $tool) => is_object($tool) ? $tool : app($tool))
            ->each(function (mixed $tool): void {
                if (! $tool instanceof Tool && ! $this->isMcpServerTool($tool)) {
                    throw new RuntimeException(sprintf(
                        'Chatbot tool [%s] must implement %s or be an MCP server tool.',
                        $tool::class,
                        Tool::class,
                    ));
                }
            })
            ->unique(fn (object $tool) => $tool::class)
            ->values()
            ->all();
    }

    protected function isMcpServerTool(mixed $tool): bool
    {
        return class_exists(McpServerTool::class) && McpServerTool::supports($tool);
    }
}
