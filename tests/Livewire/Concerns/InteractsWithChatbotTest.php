<?php

namespace Wotz\FilamentChatbot\Tests\Livewire\Concerns;

use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Contracts\HasChatbotContext;
use Wotz\FilamentChatbot\Livewire\Concerns\InteractsWithChatbot;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;
use Wotz\FilamentChatbot\Tests\TestCase;

class InteractsWithChatbotTest extends TestCase
{
    #[Test]
    public function it_prefers_explicit_chatbot_context_when_the_component_provides_it(): void
    {
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

        $this->assertSame(
            ['type' => 'custom', 'id' => 42],
            $component->exposedChatbotContextPayload(),
        );
    }

    #[Test]
    public function it_uses_the_current_record_and_its_already_loaded_relations_as_context(): void
    {
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

        $this->assertArrayHasKey('id', $context);
        $this->assertSame($conversation->id, $context['id']);
        $this->assertArrayHasKey('title', $context);
        $this->assertSame('Test conversation', $context['title']);
        $this->assertArrayHasKey('messages', $context);
        $this->assertCount(1, $context['messages']);
        $this->assertArrayHasKey('content', $context['messages'][0]);
        $this->assertSame('Loaded reply', $context['messages'][0]['content']);
    }

    #[Test]
    public function it_does_not_include_relations_that_were_not_already_loaded(): void
    {
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

        $this->assertArrayHasKey('id', $context);
        $this->assertSame($conversation->id, $context['id']);
        $this->assertArrayNotHasKey('messages', $context);
    }

    #[Test]
    public function it_returns_an_empty_payload_when_there_is_no_custom_context_and_no_record(): void
    {
        $component = new class
        {
            use InteractsWithChatbot;

            public function exposedChatbotContextPayload(): array
            {
                return $this->resolveChatbotContextPayload();
            }
        };

        $this->assertSame([], $component->exposedChatbotContextPayload());
    }
}
