<?php

namespace Wotz\FilamentChatbot\Contracts;

interface HasChatbotContext
{
    /**
     * @return array<string, mixed>
     */
    public function chatbotContext(): array;
}
