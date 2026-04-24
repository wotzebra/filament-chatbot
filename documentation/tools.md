# Tools

Tools allow the chatbot to perform actions during a conversation, such as looking up data, calling APIs, or executing logic. Each tool is a class that implements `Laravel\Ai\Contracts\Tool`.

Tools are instantiated through the service container, so constructor dependencies are automatically injected.

## Creating a tool class

Generate a tool with Laravel AI:

```bash
php artisan make:tool LookupOrderTool
```

Implement the required methods in the generated class:

- `description()` - explains to the AI what the tool does
- `schema()` - defines the input parameters the AI must provide
- `handle()` - executes the tool and returns the result

Official reference: [Laravel AI SDK - Tools](https://laravel.com/docs/12.x/ai-sdk#tools)

## Global tool registration

Global tools are available across all panels. Register them in `config/filament-chatbot.php` under a `tools` key:

```php
// config/filament-chatbot.php

'tools' => [
    \App\AI\Tools\LookupOrderTool::class,
    \App\AI\Tools\FetchProductTool::class,
],
```

These tools are always loaded, regardless of which panel the chatbot is used in.

## Local (per-panel) tool registration

Local tools are registered on a specific panel's plugin instance. They are merged with any globally configured tools:

```php
// App\Providers\Filament\AdminPanelProvider

use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

$panel->plugin(
    ChatbotPlugin::make()->tools([
        \App\AI\Tools\AdminReportTool::class,
    ])
);
```

Tools registered this way are only available in that specific panel. If the same tool class is registered both globally and locally, it will only be passed to the agent once.
