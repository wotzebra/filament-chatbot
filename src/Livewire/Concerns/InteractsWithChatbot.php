<?php

namespace Wotz\FilamentChatbot\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Wotz\FilamentChatbot\Contracts\HasChatbotContext;

trait InteractsWithChatbot
{
    public function bootedInteractsWithChatbot(): void
    {
        $context = $this->resolveChatbotContextPayload();

        if ($context === []) {
            return;
        }

        $this->dispatch('chatbot:context-updated', context: $context);
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveChatbotContextPayload(): array
    {
        if ($this instanceof HasChatbotContext) {
            return $this->chatbotContext();
        }

        $record = $this->resolveChatbotRecord();

        if (! $record) {
            return [];
        }

        /** @var array<string, mixed> $context */
        $context = $record->toArray();

        return $context;
    }

    protected function resolveChatbotRecord(): ?Model
    {
        if (method_exists($this, 'getRecord')) {
            $record = $this->getRecord();

            if ($record instanceof Model) {
                return $record;
            }
        }

        if (property_exists($this, 'record') && $this->record instanceof Model) {
            return $this->record;
        }

        return null;
    }
}
