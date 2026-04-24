<?php

namespace Wotz\FilamentChatbot\Support\Chatbot;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Services\ChatConfig;
use Wotz\FilamentChatbot\Services\PendingChat;

class PreparePendingChat
{
    public function __construct(protected ChatConfig $defaults) {}

    public function fromPlugin(
        string $conversationId,
        ?Authenticatable $user,
        ?ChatbotPlugin $chatbot,
        array $rawContext = [],
        ?Request $request = null,
    ): PendingChat {
        return $this->fromOverrides(
            conversationId: $conversationId,
            user: $user,
            overrides: $this->resolveOverrides($chatbot, $rawContext, $request),
        );
    }

    public function fromOverrides(
        string $conversationId,
        ?Authenticatable $user,
        ?ChatOverrides $overrides = null,
    ): PendingChat {
        $pending = new PendingChat(clone $this->defaults, $conversationId);

        $pending->as($user);

        if ($overrides !== null) {
            $pending->applyOverrides($overrides);
        }

        return $pending;
    }

    public function resolveOverrides(
        ?ChatbotPlugin $chatbot,
        array $rawContext = [],
        ?Request $request = null,
    ): ?ChatOverrides {
        if ($chatbot === null) {
            return null;
        }

        $request ??= request();
        $resolver = $chatbot->getContextResolver();

        return new ChatOverrides(
            agent: $chatbot->getAgentClass(),
            provider: $chatbot->getProvider(),
            model: $chatbot->getModel(),
            tools: $chatbot->getTools(),
            context: $resolver($rawContext, $request),
        );
    }
}
