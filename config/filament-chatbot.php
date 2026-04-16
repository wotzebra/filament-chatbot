<?php

use Wotz\FilamentChatbot\Agents\Assistant;

return [
    /*
    |--------------------------------------------------------------------------
    | Chatbot Enabled
    |--------------------------------------------------------------------------
    |
    | This value determines whether the chatbot widget is rendered within
    | Filament panels. When set to null (the default), the widget will only
    | be shown to authenticated users. Set to true/false to override.
    |
    */

    'enabled' => env('FILAMENT_CHATBOT_ENABLED'),

    /*
    |--------------------------------------------------------------------------
    | Default Agent
    |--------------------------------------------------------------------------
    |
    | The agent class used by the chatbot. Each agent class may define its
    | own model and instructions, or fall back to the values below. You may
    | override this per panel by calling agent() on the ChatbotPlugin.
    |
    */

    'agent' => Assistant::class,

    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    |
    | The AI provider used by the chatbot. When null, the default provider
    | from config/ai.php is used. You may override this per panel by
    | calling provider() on the ChatbotPlugin.
    |
    */

    'provider' => env('FILAMENT_CHATBOT_PROVIDER'),

    /*
    |--------------------------------------------------------------------------
    | AI Model
    |--------------------------------------------------------------------------
    |
    | The AI model used by the default agent. Make sure the corresponding
    | provider API key is set in your .env (e.g. OPENAI_API_KEY). You may
    | override this per panel by calling model() on the ChatbotPlugin.
    |
    */

    'model' => env('FILAMENT_CHATBOT_MODEL', 'gpt-4o-mini'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | How long the chatbot will wait for a response from the AI provider
    | before timing out, expressed in seconds.
    |
    */

    'timeout' => (int) env('FILAMENT_CHATBOT_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | System Instructions
    |--------------------------------------------------------------------------
    |
    | The system prompt sent to the AI model on every request. Agent classes
    | may use this value or define their own instructions independently.
    |
    */

    'instructions' => '',

    /*
    |--------------------------------------------------------------------------
    | Tools
    |--------------------------------------------------------------------------
    |
    | An array of tool classes that the chatbot agent may use. Each class
    | must implement Laravel\Ai\Contracts\Tool. Tools configured here are
    | merged with any tools added per panel via the plugin API.
    |
    */

    'tools' => [],

    /*
    |--------------------------------------------------------------------------
    | Stream Route Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware applied to the streaming endpoint used by the chatbot.
    |
    */

    'route_middleware' => ['auth', 'web'],
];
