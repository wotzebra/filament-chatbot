<?php

namespace Wotz\FilamentChatbot\Filament\Resources\ConversationResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Wotz\FilamentChatbot\Filament\Resources\ConversationResource;

class ViewConversation extends ViewRecord
{
    protected static string $resource = ConversationResource::class;

    protected string $view = 'filament-chatbot::pages.view-conversation';
}