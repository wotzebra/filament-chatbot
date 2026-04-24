# Filament Chatbot

A Filament plugin (v4, v5) that adds a floating, streaming chatbot widget to your panel using Laravel AI.

## Requirements

- PHP 8.3+
- Filament 4.x or 5.x
- [Laravel AI](https://github.com/laravel/ai) (installed automatically as a dependency)

## Quick start

```bash
composer require wotz/filament-chatbot
php artisan filament-chatbot:install
```

Configure your AI provider in `config/ai.php`, then register the plugin in your Filament panel provider:

```php
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

$panel->plugin(ChatbotPlugin::make());
```

## Documentation

Full documentation lives in [`documentation/`](documentation/index.md):

- [Installation](documentation/installation.md)
- [Plugin API](documentation/plugin-api.md)
- [Configuration reference](documentation/configuration.md)
- [Custom agent](documentation/agents.md)
- [Tools](documentation/tools.md)
- [Conversations](documentation/conversations.md)
- [Page context](documentation/page-context.md)
- [UI customization](documentation/ui-customization.md)
- [Streaming (HTTP / WebSocket)](documentation/streaming.md)

## Project links

- [Changelog](CHANGELOG.md)
- [Upgrade guide](UPGRADING.md)
- [Contributing](CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)

## Security

If you discover a security vulnerability, please email `info@codedor.be` instead of opening a public issue.

## License

MIT. See [LICENSE.md](LICENSE.md).
