# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0]

### Added

- Initial release of the Filament chatbot package.
- Filament panel plugin integration with a floating chatbot widget.
- Streaming chat responses via Laravel AI.
- Persistent conversations and messages through package migrations.
- Configurable chatbot agent, provider, model, timeout, and UI settings.
- Configurable system prompt (instructions) sent to the AI model on every request.
- Support for registering Laravel AI tools through plugin configuration.
- Logo URL support for the chatbot widget header.
- All plugin configuration options accept closures for dynamic resolution.
- Configurable streaming route name, path, and middleware.
- Session-persisted panel visibility and window position.
