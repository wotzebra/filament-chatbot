<?php

use Wotz\FilamentChatbot\Contracts\HasChatbotContext;
use Wotz\FilamentChatbot\Livewire\Concerns\InteractsWithChatbot;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;

it('prefers explicit chatbot context when the component provides it', function () {
    $component = new class implements HasChatbotContext
    {
        use InteractsWithChatbot;

        public function chatbotContext(): array
        {
            return ['type' => 'custom', 'id' => 42];
        }

        public function exposedChatbotContextPayload(): array
        {
            return $this->resolveChatbotContextPayload();
        }
    };

    expect($component->exposedChatbotContextPayload())
        ->toBe(['type' => 'custom', 'id' => 42]);
});

it('uses the current record and its already loaded relations as context', function () {
    $conversation = AgentConversation::factory()->create(['title' => 'Test conversation']);

    AgentConversationMessage::factory()
        ->assistant()
        ->for($conversation, 'conversation')
        ->create(['content' => 'Loaded reply']);

    $conversation->load('messages');

    $component = new class($conversation)
    {
        use InteractsWithChatbot;

        public function __construct(public AgentConversation $record) {}

        public function getRecord(): AgentConversation
        {
            return $this->record;
        }

        public function exposedChatbotContextPayload(): array
        {
            return $this->resolveChatbotContextPayload();
        }
    };

    $context = $component->exposedChatbotContextPayload();

    expect($context)
        ->toHaveKey('id', $conversation->id)
        ->and($context)->toHaveKey('title', 'Test conversation')
        ->and($context)->toHaveKey('messages')
        ->and($context['messages'])->toHaveCount(1)
        ->and($context['messages'][0])->toHaveKey('content', 'Loaded reply');
});

it('does not include relations that were not already loaded', function () {
    $conversation = AgentConversation::factory()->create(['title' => 'Without relations']);

    AgentConversationMessage::factory()
        ->assistant()
        ->for($conversation, 'conversation')
        ->create();

    $component = new class($conversation)
    {
        use InteractsWithChatbot;

        public function __construct(public AgentConversation $record) {}

        public function getRecord(): AgentConversation
        {
            return $this->record;
        }

        public function exposedChatbotContextPayload(): array
        {
            return $this->resolveChatbotContextPayload();
        }
    };

    $context = $component->exposedChatbotContextPayload();

    expect($context)
        ->toHaveKey('id', $conversation->id)
        ->not->toHaveKey('messages');
});

it('returns an empty payload when there is no custom context and no record', function () {
    $component = new class
    {
        use InteractsWithChatbot;

        public function exposedChatbotContextPayload(): array
        {
            return $this->resolveChatbotContextPayload();
        }
    };

    expect($component->exposedChatbotContextPayload())->toBe([]);
});
