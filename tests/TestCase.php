<?php

namespace Wotz\FilamentChatbot\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Wotz\FilamentChatbot\Providers\FilamentChatbotServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Wotz\\FilamentChatbot\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            \BladeUI\Icons\BladeIconsServiceProvider::class,
            \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
            \Filament\Support\SupportServiceProvider::class,
            \Filament\FilamentServiceProvider::class,
            \Livewire\LivewireServiceProvider::class,
            \Laravel\Ai\AiServiceProvider::class,
            FilamentChatbotServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }
}
