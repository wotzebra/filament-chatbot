<?php

namespace Wotz\FilamentChatbot\Tests;

use Filament\Facades\Filament;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

class FilamentTestCase extends TestCase
{
    use RefreshDatabase;

    protected ChatbotPlugin $chatbotPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('test'));

        $this->chatbotPlugin = Filament::getPanel('test')->getPlugin('chatbot');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function makeTestUser(): User
    {
        $user = new User;
        $user->id = 1;
        $user->name = 'Test User';

        return $user;
    }
}
