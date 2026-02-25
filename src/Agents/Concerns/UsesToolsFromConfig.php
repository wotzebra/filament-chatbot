<?php

namespace Wotz\FilamentChatbot\Agents\Concerns;

use Wotz\FilamentChatbot\Support\Chatbot\ToolRegistry;

trait UsesToolsFromConfig
{
    public function tools(): iterable
    {
        return app(ToolRegistry::class)->resolveTools();
    }
}
