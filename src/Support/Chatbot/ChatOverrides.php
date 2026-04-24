<?php

namespace Wotz\FilamentChatbot\Support\Chatbot;

final readonly class ChatOverrides
{
    /**
     * @param  array<int, mixed>  $tools
     */
    public function __construct(
        public ?string $agent = null,
        public string|array|null $provider = null,
        public ?string $model = null,
        public array $tools = [],
        public ?string $context = null,
    ) {}
}
