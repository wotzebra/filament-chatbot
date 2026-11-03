<?php

namespace Wotz\FilamentChatbot\Tests\Livewire\ChatbotWidget;

use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Livewire\ChatbotWidget;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;
use Wotz\FilamentChatbot\Tests\TestCase;

class StandaloneModeTest extends TestCase
{
    #[Test]
    public function it_mounts_in_standalone_mode_when_a_conversation_id_is_provided(): void
    {
        $user = $this->makeTestUser();
        $conversation = AgentConversation::factory()->ownedBy($user)->create();

        AgentConversationMessage::factory()->user()->for($conversation, 'conversation')->create();
        AgentConversationMessage::factory()->assistant()->for($conversation, 'conversation')->create();

        $component = Livewire::actingAs($user)->test(ChatbotWidget::class, ['conversationId' => $conversation->id])
            ->assertSet('standalone', true)
            ->assertSet('conversationId', $conversation->id)
            ->assertSet('isStreaming', false);

        $this->assertCount(2, $component->get('messages'));
    }

    #[Test]
    public function it_renders_the_widget_view_without_errors_in_standalone_mode(): void
    {
        $user = $this->makeTestUser();
        $conversation = AgentConversation::factory()->ownedBy($user)->create();

        Livewire::actingAs($user)->test(ChatbotWidget::class, ['conversationId' => $conversation->id])
            ->assertOk()
            ->assertSee('chatbot-input', false);
    }

    #[Test]
    public function it_aborts_with_403_when_the_conversation_is_owned_by_another_user(): void
    {
        $conversation = AgentConversation::factory()->ownedBy($this->makeOtherUser())->create();

        Livewire::actingAs($this->makeTestUser())
            ->test(ChatbotWidget::class, ['conversationId' => $conversation->id])
            ->assertForbidden();
    }
}
