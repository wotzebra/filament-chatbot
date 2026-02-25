<?php

namespace Wotz\FilamentChatbot\Agents;

use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;
use Wotz\FilamentChatbot\Agents\Concerns\UsesToolsFromConfig;

class Assistant implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;
    use UsesToolsFromConfig;

    public function instructions(): Stringable|string
    {
        return (string) config('filament-chatbot.instructions');
    }

    public function provider(): string|array|null
    {
        return config('filament-chatbot.provider');
    }

    public function model(): ?string
    {
        return config('filament-chatbot.model');
    }

    public function timeout(): int
    {
        return (int) config('filament-chatbot.timeout', 60);
    }
}
