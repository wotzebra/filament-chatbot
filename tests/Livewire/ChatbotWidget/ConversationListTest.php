<?php

namespace Wotz\FilamentChatbot\Tests\Livewire\ChatbotWidget;

use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Livewire\ChatbotWidget;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;
use Wotz\FilamentChatbot\Tests\TestCase;

class ConversationListTest extends TestCase
{
    #[Test]
    public function it_loads_recent_conversations_into_the_sidebar_list_sorted_by_updated_at_desc(): void
    {
        $older = AgentConversation::factory()->create([
            'title' => 'Older thread',
            'updated_at' => now()->subHour(),
        ]);
        $newer = AgentConversation::factory()->create([
            'title' => 'Newest thread',
            'updated_at' => now(),
        ]);

        $conversations = Livewire::test(ChatbotWidget::class)->get('conversations');

        $this->assertCount(2, $conversations);
        $this->assertSame([$newer->id, $older->id], $this->conversationIds($conversations));
    }

    #[Test]
    public function it_keeps_the_sidebar_list_sorted_when_opening_another_conversation(): void
    {
        $older = AgentConversation::factory()->create([
            'title' => 'Older thread',
            'updated_at' => now()->subHour(),
        ]);
        $newer = AgentConversation::factory()->create([
            'title' => 'Newest thread',
            'updated_at' => now(),
        ]);

        AgentConversationMessage::factory()->assistant()->for($older, 'conversation')->create();
        AgentConversationMessage::factory()->assistant()->for($newer, 'conversation')->create();

        $component = Livewire::test(ChatbotWidget::class);

        $this->assertSame([$newer->id, $older->id], $this->conversationIds($component->get('conversations')));

        $component->call('openConversation', $older->id);

        $this->assertSame([$newer->id, $older->id], $this->conversationIds($component->get('conversations')));
    }

    #[Test]
    public function it_can_switch_back_to_an_earlier_conversation_while_another_one_is_open(): void
    {
        $first = AgentConversation::factory()->create(['title' => 'First']);
        $second = AgentConversation::factory()->create(['title' => 'Second']);

        AgentConversationMessage::factory()->user()->for($first, 'conversation')->create(['content' => 'First question']);
        AgentConversationMessage::factory()->assistant()->for($first, 'conversation')->create(['content' => 'First answer']);
        AgentConversationMessage::factory()->user()->for($second, 'conversation')->create(['content' => 'Second question']);
        AgentConversationMessage::factory()->assistant()->for($second, 'conversation')->create(['content' => 'Second answer']);

        session()->put($this->conversationKey(), $second->id);

        Livewire::test(ChatbotWidget::class)
            ->call('openConversation', $first->id)
            ->assertSet('conversationId', $first->id)
            ->assertSet('messages.0.content', 'First question')
            ->assertSet('messages.1.content', 'First answer');
    }

    #[Test]
    public function it_can_open_any_owned_conversation(): void
    {
        $conversation = AgentConversation::factory()->create(['title' => 'Archived']);

        AgentConversationMessage::factory()->user()->for($conversation, 'conversation')->create(['content' => 'Archived question']);
        AgentConversationMessage::factory()->assistant()->for($conversation, 'conversation')->create(['content' => 'Archived answer']);

        Livewire::test(ChatbotWidget::class)
            ->call('openConversation', $conversation->id)
            ->assertSet('conversationId', $conversation->id)
            ->assertSet('messages.0.content', 'Archived question')
            ->assertSet('messages.1.content', 'Archived answer');
    }

    #[Test]
    public function it_renders_compact_conversation_browser_controls_for_the_floating_widget(): void
    {
        Livewire::test(ChatbotWidget::class)
            ->assertSeeInOrder([
                __('filament-chatbot::chatbot.new_conversation'),
                __('filament-chatbot::chatbot.browse_conversations'),
            ], false)
            ->assertSee('showConversationBrowser', false);
    }

    #[Test]
    public function it_renders_the_browser_list_with_titles_only_and_hides_message_previews(): void
    {
        $conversation = AgentConversation::factory()->create([
            'title' => 'Title only thread',
        ]);

        AgentConversationMessage::factory()
            ->assistant()
            ->for($conversation, 'conversation')
            ->create(['content' => 'This preview should stay out of the browser list']);

        Livewire::test(ChatbotWidget::class)
            ->assertSee('Title only thread')
            ->assertDontSee('This preview should stay out of the browser list')
            ->assertDontSee(__('filament-chatbot::chatbot.new_conversation_hint'));
    }
}
