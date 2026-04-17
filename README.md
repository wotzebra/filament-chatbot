# Filament Chatbot

A Filament plugin (v4, v5) that adds a floating, streaming chatbot widget to your panel using Laravel AI.

## Documentation

For detailed setup instructions and the complete reference, see the full documentation:

- [Installation](docs/index.md#installation)
- [Register the Filament plugin](docs/index.md#register-the-filament-plugin)
- [Plugin API](docs/index.md#plugin-api)
- [Configuration reference](docs/index.md#configuration-reference)
- [Custom agent](docs/index.md#custom-agent)
- [Tools](docs/index.md#tools)
- [Conversations and persistence](docs/index.md#conversations-and-persistence)
- [Conversation resource](docs/index.md#conversation-resource)

**Full documentation:** [docs/index.md](docs/index.md)

## Requirements

- PHP 8.2+
- Filament 4.x or 5.x
- [Laravel AI](https://github.com/laravel/ai) (installed automatically as a dependency)

## Quick Start

Install the package:

```bash
composer require wotz/filament-chatbot
```

Run the install command. This publishes the config file and migrations, and optionally runs the migrations:

```bash
php artisan filament-chatbot:install
```

Configure your AI provider in `config/ai.php` by adding the API key for your chosen provider. The chatbot uses whichever provider is set as `default` in that file.

Register the plugin in your Filament panel provider:

```php
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

$panel->plugin(ChatbotPlugin::make());
```

## Project Links

- [Changelog](CHANGELOG.md)
- [Upgrade guide](UPGRADING.md)
- [Contributing](CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)

## Security

If you discover a security vulnerability, please email `info@codedor.be` instead of opening a public issue.

## License

MIT. See [LICENSE.md](LICENSE.md).
