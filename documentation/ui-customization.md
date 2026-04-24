# UI customization

## Welcome message

The welcome message supports markdown, so you can use bold text, links, lists, and other formatting:

```php
ChatbotPlugin::make()
    ->welcomeMessage('Hello! I can help you with:\n- **Orders**\n- **Returns**\n- **Account questions**')
```

## Logo

By default, bot messages show a sparkle icon. You can replace this with a custom image:

```php
ChatbotPlugin::make()
    ->logoUrl(asset('images/bot-avatar.png'))
```

## Window dimensions and position

The chat window defaults to 400×600px and appears in the bottom-right corner. Users can toggle the position to the left side. This preference is persisted in the session.

```php
ChatbotPlugin::make()
    ->chatWidth('500px')
    ->chatHeight('700px')
```
