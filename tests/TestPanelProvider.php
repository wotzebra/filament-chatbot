<?php

namespace Wotz\FilamentChatbot\Tests;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotResourcePlugin;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('test')
            ->path('test')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugin(
                ChatbotPlugin::make()
                    ->agent(Assistant::class)
                    ->conversationKey('chatbot_test_key')
                    ->botName('Test Bot')
                    ->welcomeMessage('Welcome to the chatbot!')
                    ->buttonText('Open Chat')
                    ->buttonIcon('heroicon-o-chat-bubble-left-right')
                    ->chatWidth('400px')
                    ->chatHeight('600px')
            )
            ->plugin(ChatbotResourcePlugin::make());
    }
}