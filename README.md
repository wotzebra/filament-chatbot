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

**Full documentation:** [docs/index.md](docs/index.md)

## Requirements

This package uses [Laravel AI](https://github.com/laravel/ai) to communicate with AI providers. You must install and configure Laravel AI first.

## Quick Start

Install the package:

```bash
composer require wotz/filament-chatbot
```

Publish and run the Laravel AI migrations (required for conversation storage):

```bash
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate
```

In `config/ai.php`, add the API key for your chosen provider. The chatbot will use whichever provider is set as `default` in that file.

Publish the chatbot config:

```bash
php artisan vendor:publish --tag="filament-chatbot-config"
```

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
