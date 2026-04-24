<?php

namespace Wotz\FilamentChatbot\Agents;

use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;
use Wotz\FilamentChatbot\Agents\Concerns\ComposesInstructionsWithContext;
use Wotz\FilamentChatbot\Agents\Concerns\UsesToolsFromConfig;

#[MaxSteps(5)]
#[MaxTokens(500)]
#[Temperature(0.7)]
class Assistant implements Agent, Conversational, HasTools
{
    use ComposesInstructionsWithContext;
    use Promptable;
    use RemembersConversations;
    use UsesToolsFromConfig;

    public function instructions(): Stringable|string
    {
        return $this->composeInstructions((string) config('filament-chatbot.instructions'));
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
        return config('filament-chatbot.timeout', 60);
    }
}
