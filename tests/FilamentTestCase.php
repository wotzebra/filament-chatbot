<?php

namespace Wotz\FilamentChatbot\Tests;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

class FilamentTestCase extends TestCase
{
    use RefreshDatabase;

    protected ChatbotPlugin $chatbotPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFilamentPanel();
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function setUpFilamentPanel(): void
    {
        $this->chatbotPlugin = ChatbotPlugin::make()
            ->agent(Assistant::class)
            ->conversationKey('chatbot_test_key')
            ->botName('Test Bot')
            ->welcomeMessage('Welcome to the chatbot!')
            ->buttonText('Open Chat')
            ->buttonIcon('heroicon-o-chat-bubble-left-right')
            ->chatWidth('400px')
            ->chatHeight('600px');

        $panel = Panel::make()->id('test')->plugin($this->chatbotPlugin);

        Filament::setCurrentPanel($panel);
    }

    protected function makeTestUser(): User
    {
        $user = new User;
        $user->id = 1;
        $user->name = 'Test User';

        return $user;
    }
}
