# Upgrading

## Versioning Policy

This package follows Semantic Versioning:

- `MAJOR`: breaking changes
- `MINOR`: new backward-compatible features
- `PATCH`: backward-compatible fixes

## Upgrade Checklist

1. Review the [CHANGELOG](CHANGELOG.md) for breaking changes and migration notes.
2. Update the package:

```bash
composer update wotz/filament-chatbot
```

3. Publish and run new migrations when a release includes schema updates:

```bash
php artisan vendor:publish --tag="filament-chatbot-migrations" --force
php artisan migrate
```

4. Compare your `config/filament-chatbot.php` to the latest package config and add new keys when needed.
5. Verify your Filament panel plugin registration and chatbot customizations still match your application needs.
6. Run your test suite.

## Upgrading to laravel/ai 1.x

This release requires `laravel/ai` `^1.3`, which changes the conversation tables.

1. Update the package together with laravel/ai and laravel/mcp:

```bash
composer require wotz/filament-chatbot laravel/ai:^1.3 laravel/mcp:^1.0 -W
```

2. Publish and run the upgrade migration. It moves `user_id` to `participant_type` / `participant_id`, rewrites `tool_calls` / `tool_results` as `steps`, and adds the `status` column:

```bash
php artisan vendor:publish --tag="filament-chatbot-migrations"
php artisan migrate
```

The migration sets `participant_type` to the morph class of `filament-chatbot.user_model`, or of `auth.providers.users.model` when that is not set. If a panel uses a different model through `->userModel()`, update the `participant_type` of those conversations yourself.

3. Replace references to `AgentConversation::user()` or the `user_id` column with `participant()`, `participant_type` and `participant_id`.

4. Review the [laravel/ai upgrade guide](https://github.com/laravel/ai/blob/main/UPGRADE.md) for changes to your own agents and tools.

## Notes

If a future major release requires manual code changes, detailed steps will be documented in this file under a dedicated version section.
