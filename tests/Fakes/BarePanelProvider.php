<?php

namespace Wotz\FilamentChatbot\Tests\Fakes;

use Filament\Panel;
use Filament\PanelProvider;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

/**
 * A panel with the chatbot widget but without the conversation resource.
 */
class BarePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('bare')
            ->path('bare')
            ->plugin(
                ChatbotPlugin::make()
                    ->agent(Assistant::class)
                    ->conversationKey('chatbot_bare_key')
            );
    }
}
