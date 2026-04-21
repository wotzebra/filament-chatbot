<?php

namespace Wotz\FilamentChatbot\Http\Controllers;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Wotz\FilamentChatbot\Facades\Chat;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Http\Requests\ChatStreamRequest;
use Wotz\FilamentChatbot\Streaming\TransportManager;

class ChatStreamController
{
    public function __invoke(ChatStreamRequest $request, TransportManager $transports): SymfonyResponse
    {
        set_time_limit(300);

        abort_unless(Chat::ownedBy($request->conversationId(), auth()->user()), 403);

        /** @var ChatbotPlugin $chatbot */
        $chatbot = filament('chatbot');

        $resolver = $chatbot->getContextResolver();

        $events = Chat::for($request->conversationId())
            ->as(auth()->user())
            ->applyOverrides([
                'agent' => $chatbot->getAgentClass(),
                'provider' => $chatbot->getProvider(),
                'model' => $chatbot->getModel(),
                'tools' => $chatbot->getTools(),
                'context' => $resolver($request->context(), $request),
            ])
            ->streamEvents($request->message());

        $transport = $transports->driver($request->transport());

        return $transport->start(
            $request->conversationId(),
            $request->message(),
            $events,
        );
    }
}
