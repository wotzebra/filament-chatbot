<?php

namespace Wotz\FilamentChatbot\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Support\SupportServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\AiServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Providers\FilamentChatbotServiceProvider;
use Wotz\FilamentChatbot\Tests\Fakes\TestPanelProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected ChatbotPlugin $chatbotPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Wotz\\FilamentChatbot\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        Filament::setCurrentPanel(Filament::getPanel('test'));

        $this->chatbotPlugin = Filament::getPanel('test')->getPlugin('chatbot');
    }

    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            FilamentServiceProvider::class,
            LivewireServiceProvider::class,
            AiServiceProvider::class,
            FilamentChatbotServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
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

    protected function conversationKey(): string
    {
        return filament('chatbot')->getConversationKey();
    }

    protected function activeStreamsKey(): string
    {
        return $this->conversationKey() . '_active_streams';
    }

    protected function conversationIds(mixed $conversations): array
    {
        return collect($conversations)->pluck('id')->all();
    }
}
