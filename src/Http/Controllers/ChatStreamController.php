<?php

namespace Wotz\FilamentChatbot\Http\Controllers;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Wotz\FilamentChatbot\Facades\Chat;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Http\Requests\ChatStreamRequest;
use Wotz\FilamentChatbot\Streaming\TransportManager;
use Wotz\FilamentChatbot\Support\Chatbot\PreparePendingChat;

class ChatStreamController
{
    public function __invoke(
        ChatStreamRequest $request,
        TransportManager $transports,
        PreparePendingChat $preparePendingChat,
    ): SymfonyResponse {
        abort_unless(Chat::ownedBy($request->conversationId(), auth()->user()), 403);

        /** @var ChatbotPlugin $chatbot */
        $chatbot = filament('chatbot');

        $pending = $preparePendingChat->fromPlugin(
            conversationId: $request->conversationId(),
            user: auth()->user(),
            chatbot: $chatbot,
            rawContext: $request->context(),
            request: $request,
        );
        $eventsFactory = fn (): iterable => $pending->streamResponse($request->message());

        $transport = $transports->driver($request->transport());

        return $transport->start(
            $request->conversationId(),
            $request->message(),
            $eventsFactory,
        );
    }
}
