<?php

namespace Wotz\FilamentChatbot\Tests\Livewire\ChatbotWidget;

use Filament\Facades\Filament;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Livewire\ChatbotWidget;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Tests\TestCase;

class FullscreenLinkTest extends TestCase
{
    #[Test]
    public function it_links_to_the_conversation_page_when_the_resource_plugin_is_registered(): void
    {
        $conversation = AgentConversation::factory()->create();

        session()->put($this->conversationKey(), $conversation->id);

        Livewire::test(ChatbotWidget::class)
            ->assertOk()
            ->assertSet('conversationId', $conversation->id)
            ->assertSee(__('filament-chatbot::chatbot.open_fullscreen'))
            ->assertSee('/test/conversations/' . $conversation->id);
    }

    #[Test]
    public function it_renders_without_a_fullscreen_link_when_the_resource_plugin_is_missing(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('bare'));

        $conversation = AgentConversation::factory()->create();

        session()->put('chatbot_bare_key', $conversation->id);

        Livewire::test(ChatbotWidget::class)
            ->assertOk()
            ->assertSet('conversationId', $conversation->id)
            ->assertDontSee(__('filament-chatbot::chatbot.open_fullscreen'))
            ->assertDontSee('/conversations/' . $conversation->id);
    }
}
