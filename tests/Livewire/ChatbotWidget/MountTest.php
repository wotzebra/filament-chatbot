<?php

namespace Wotz\FilamentChatbot\Tests\Livewire\ChatbotWidget;

use Laravel\Ai\Messages\MessageRole;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Livewire\ChatbotWidget;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;
use Wotz\FilamentChatbot\Tests\TestCase;

class MountTest extends TestCase
{
    #[Test]
    public function it_mounts_with_empty_state_when_the_session_is_empty(): void
    {
        Livewire::test(ChatbotWidget::class)
            ->assertSet('conversationId', null)
            ->assertSet('isStreaming', false)
            ->assertSet('panelHidden', true)
            ->assertSet('messages', [])
            ->assertSet('pageContext', []);
    }

    #[Test]
    public function it_loads_existing_messages_when_the_session_holds_a_conversation_id(): void
    {
        $conversation = AgentConversation::factory()->create();

        AgentConversationMessage::factory()->user()->for($conversation, 'conversation')->create();
        AgentConversationMessage::factory()->assistant()->for($conversation, 'conversation')->create();

        session()->put($this->conversationKey(), $conversation->id);

        $component = Livewire::test(ChatbotWidget::class)
            ->assertSet('conversationId', $conversation->id);

        $messages = $component->get('messages');

        $this->assertCount(2, $messages);
        $this->assertSame(MessageRole::User->value, $messages[0]['role']);
        $this->assertSame(MessageRole::Assistant->value, $messages[1]['role']);
    }

    #[Test]
    public function it_ignores_a_session_conversation_id_that_no_longer_exists(): void
    {
        session()->put($this->conversationKey(), fake()->uuid());

        $component = Livewire::test(ChatbotWidget::class);

        $this->assertNull($component->get('conversationId'));
        $this->assertSame([], $component->get('messages'));
        $this->assertSame('', session()->get($this->conversationKey()));
    }

    #[Test]
    public function it_ignores_a_session_conversation_id_that_is_not_owned_by_the_current_user(): void
    {
        $conversation = AgentConversation::factory()->create(['user_id' => 999]);

        session()->put($this->conversationKey(), $conversation->id);

        $component = Livewire::actingAs($this->makeTestUser())->test(ChatbotWidget::class);

        $this->assertNull($component->get('conversationId'));
        $this->assertSame([], $component->get('messages'));
        $this->assertSame('', session()->get($this->conversationKey()));
    }

    #[Test]
    public function it_drops_an_invalid_active_stream_from_the_session_on_mount(): void
    {
        session()->put($this->activeStreamsKey(), [
            fake()->uuid() => ['message' => fake()->sentence()],
        ]);

        Livewire::test(ChatbotWidget::class)
            ->assertSet('conversationId', null)
            ->assertSet('isStreaming', false);

        $this->assertSame([], session()->get($this->activeStreamsKey()));
    }
}
