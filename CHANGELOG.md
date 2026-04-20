# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Configurable stream transport (`http` or `websocket`). The WebSocket transport uses Laravel Reverb broadcasting to deliver streaming AI responses on private per-conversation channels, enabling realtime server-to-UI pushes alongside the existing SSE transport. Selected via `FILAMENT_CHATBOT_STREAM_TRANSPORT`; defaults to `http` for backwards compatibility.

## [0.2.0]

### Added

- Support for Filament v4 and v5.
- `ChatbotResourcePlugin` - a separate plugin that registers a Filament resource for listing and viewing conversations.
- `user_model` configuration option to associate conversations with an Eloquent user model.
- Page context awareness - Filament pages can implement the `HasChatbotContext` contract to pass dynamic context data to the chatbot on every request.
- `Chat` facade with a fluent `ChatManager` / `PendingChat` service layer for programmatic chat interaction.
- `SseStream` and `ToolRegistry` support classes extracted from the controller.
- Custom Eloquent builders for `AgentConversation` and `AgentConversationMessage` models.
- Bundled compiled CSS (`resources/dist/filament-chatbot.css`) via Tailwind integration.
- English language file (`resources/lang/en/chatbot.php`) for translatable UI strings.
- `ChatbotConversation` Livewire component with a dedicated conversation detail view.

### Changed

- Refactored chat handling into a dedicated service layer (`ChatManager`, `PendingChat`, `ChatConfig`).
- Split the chatbot widget Blade view into partials (`chatbot-input`, `chatbot-messages`) for better maintainability.

## [0.1.0]

### Added

- Initial release of the Filament chatbot package.
- Filament panel plugin integration with a floating chatbot widget.
- Streaming chat responses via Laravel AI.
- Persistent conversations and messages through package migrations.
- Configurable chatbot agent, provider, model, timeout, and UI settings.
- Configurable system prompts (instructions) are sent to the AI model on every request.
- Support for registering Laravel AI tools through plugin configuration.
- Logo URL support for the chatbot widget header.
- All plugin configuration options accept closures for dynamic resolution.
- Configurable streaming route name, path, and middleware.
- Session-persisted panel visibility and window position.
