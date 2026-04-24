<?php

namespace Wotz\FilamentChatbot\Agents\Concerns;

use Wotz\FilamentChatbot\Support\Chatbot\ToolRegistry;

trait UsesToolsFromConfig
{
    /** @var array<int, mixed> */
    protected array $extraTools = [];

    /**
     * @param  array<int, mixed>  $tools
     */
    public function withExtraTools(array $tools): static
    {
        $this->extraTools = $tools;

        return $this;
    }

    public function tools(): iterable
    {
        return app(ToolRegistry::class)->resolveTools($this->extraTools);
    }
}
