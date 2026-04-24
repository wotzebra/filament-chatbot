# Filament Chatbot

`wotz/filament-chatbot` integrates a streaming AI chatbot directly into Filament panels.

It uses Laravel AI for model/provider integration, persists conversation data, and exposes a panel plugin API to customize behavior and UI.

Supported Filament versions: `4.x` and `5.x`.

## Contents

- [Installation](installation.md) - requirements, composer install, registering the plugin
- [Plugin API](plugin-api.md) - fluent configuration per panel
- [Configuration reference](configuration.md) - environment variables and config file
- [Custom agent](agents.md) - extend `Assistant` or build a fully custom agent
- [Tools](tools.md) - give the chatbot actions it can call
- [Conversations](conversations.md) - persistence, session tracking, sharing, conversation resource
- [Page context](page-context.md) - make the chatbot aware of the current page
- [UI customization](ui-customization.md) - welcome message, logo, dimensions
- [Streaming](streaming.md) - HTTP (SSE) and WebSocket (Reverb) transports

## Related

- [Changelog](../CHANGELOG.md)
- [Upgrade guide](../UPGRADING.md)
- [Contributing](../CONTRIBUTING.md)
