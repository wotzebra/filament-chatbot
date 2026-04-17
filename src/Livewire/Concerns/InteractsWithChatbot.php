<?php

namespace Wotz\FilamentChatbot\Livewire\Concerns;

use Wotz\FilamentChatbot\Contracts\HasChatbotContext;

trait InteractsWithChatbot
{
    public function bootedInteractsWithChatbot(): void
    {
        if (! $this instanceof HasChatbotContext) {
            return;
        }

        $this->dispatch('chatbot:context-updated', context: $this->chatbotContext());
    }
}
