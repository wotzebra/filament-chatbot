<?php

namespace Wotz\FilamentChatbot\Services;

class ChatConfig
{
    /**
     * @param  array<int, mixed>  $tools
     */
    public function __construct(
        public ?string $agent = null,
        public string|array|null $provider = null,
        public ?string $model = null,
        public array $tools = [],
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            agent: config('filament-chatbot.agent'),
            provider: config('filament-chatbot.provider'),
            model: config('filament-chatbot.model'),
            tools: (array) config('filament-chatbot.tools', []),
        );
    }
}
