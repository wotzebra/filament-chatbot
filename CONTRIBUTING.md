# Contributing

Thanks for contributing to `wotzebra/filament-chatbot`.

## Development Workflow

1. Fork the repository and clone your fork.

```bash
git clone git@github.com:your-username/filament-chatbot.git
cd filament-chatbot
```

2. Install dependencies.

```bash
composer install
```

3. Run tests and static checks.

```bash
vendor/bin/pest
vendor/bin/phpstan analyse
vendor/bin/pint --test
```

4. Make your changes and add/adjust tests.
5. Update docs and changelog when behavior changes.
6. Push your branch and open a pull request.

## Pull Request Guidelines

- Keep changes focused and atomic.
- Include tests for bug fixes and new features.
- Follow existing code style and architecture patterns.
- Document user-facing changes in `README.md`, `docs/index.md`, and `CHANGELOG.md`.

## Reporting Issues

Before opening an issue:

- Check existing issues for duplicates.
- Provide clear reproduction steps.
- Include relevant environment details (PHP, Laravel, Filament versions).

## Code of Conduct

By participating, you agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md).
