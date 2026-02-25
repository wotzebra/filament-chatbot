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

## Notes

If a future major release requires manual code changes, detailed steps will be documented in this file under a dedicated version section.
