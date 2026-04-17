<?php

namespace Wotz\FilamentChatbot\Filament\Resources;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Wotz\FilamentChatbot\Filament\Resources\ConversationResource\Pages\ListConversations;
use Wotz\FilamentChatbot\Filament\Resources\ConversationResource\Pages\ViewConversation;
use Wotz\FilamentChatbot\Models\AgentConversation;

class ConversationResource extends Resource
{
    protected static ?string $model = AgentConversation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $slug = 'conversations';

    protected static ?string $modelLabel = 'Conversation';

    protected static ?string $pluralModelLabel = 'Conversations';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable(),

                TextColumn::make('messages_count')
                    ->counts('messages')
                    ->label('Messages')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Conversation')
                    ->schema([
                        TextEntry::make('title'),

                        TextEntry::make('user.name')
                            ->label('User'),

                        TextEntry::make('created_at')
                            ->dateTime(),

                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Messages')
                    ->schema([
                        ViewEntry::make('messages')
                            ->view('filament-chatbot::infolists.components.conversation-messages'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConversations::route('/'),
            'view' => ViewConversation::route('/{record}'),
        ];
    }
}
