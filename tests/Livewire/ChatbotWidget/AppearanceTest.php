<?php

namespace Wotz\FilamentChatbot\Tests\Livewire\ChatbotWidget;

use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Livewire\ChatbotWidget;
use Wotz\FilamentChatbot\Tests\TestCase;

class AppearanceTest extends TestCase
{
    #[Test]
    public function it_restores_panel_hidden_state_from_the_session_on_mount(): void
    {
        session()->put('chatbot-panel-hidden', false);

        Livewire::test(ChatbotWidget::class)->assertSet('panelHidden', false);
    }

    #[Test]
    public function it_restores_the_window_position_from_the_session_on_mount(): void
    {
        session()->put('chatbot-win-position', 'left');

        Livewire::test(ChatbotWidget::class)->assertSet('winPosition', 'left');
    }

    #[Test]
    public function it_toggles_the_panel_hidden_state_and_persists_it_to_the_session(): void
    {
        $component = Livewire::test(ChatbotWidget::class)->assertSet('panelHidden', true);

        $component->call('togglePanel')->assertSet('panelHidden', false);
        $this->assertFalse(session()->get('chatbot-panel-hidden'));

        $component->call('togglePanel')->assertSet('panelHidden', true);
        $this->assertTrue(session()->get('chatbot-panel-hidden'));
    }

    #[Test]
    public function it_toggles_the_window_position_between_left_and_default_and_persists_it_to_the_session(): void
    {
        $component = Livewire::test(ChatbotWidget::class);

        $component->call('changeWinPosition')->assertSet('winPosition', 'left');
        $this->assertSame('left', session()->get('chatbot-win-position'));

        $component->call('changeWinPosition')->assertSet('winPosition', '');
        $this->assertSame('', session()->get('chatbot-win-position'));
    }

    #[Test]
    public function it_stores_the_page_context_when_the_chatbot_context_updated_event_is_dispatched(): void
    {
        $context = ['type' => 'order', 'id' => 42];

        Livewire::test(ChatbotWidget::class)
            ->dispatch('chatbot:context-updated', context: $context)
            ->assertSet('pageContext', $context);
    }
}
