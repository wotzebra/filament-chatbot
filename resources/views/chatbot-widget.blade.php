<div
    @if (! $standalone) id="filament-chatbot-window" @endif
    x-data="{}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-chatbot', package: 'wotz/filament-chatbot'))]"
    @class([
        'flex h-[calc(100vh-12rem)] flex-col overflow-hidden rounded-xl border border-gray-200/90 bg-white/95 shadow-lg' => $standalone,
    ])>
    @if ($standalone)
        @include('filament-chatbot::partials.chatbot-messages')
        @include('filament-chatbot::partials.chatbot-input')
    @else
        {{-- Toggle button: only visible when panel is hidden --}}
        <div @class([
            'fixed bottom-4 right-4 z-50 cursor-pointer',
            'hidden' => ! $panelHidden,
        ])>
            <x-filament::button wire:click="togglePanel" id="btn-chat" :icon="$buttonIcon" color="primary">
                {{ $buttonText }}
            </x-filament::button>
        </div>

        {{-- Chat window --}}
        <div
            @class([
                'fixed bottom-0 z-50 flex flex-col overflow-hidden border border-gray-200/90 bg-white/95 shadow-2xl backdrop-blur-sm',
                'left-0 rounded-t-xl' => $winPosition === 'left',
                'right-0 rounded-tl-xl' => $winPosition !== 'left',
                'hidden' => $panelHidden,
            ])
            style="{{ $winWidth }} {{ $winHeight }}"
            id="chat-window">

            {{-- Header --}}
            <div class="flex items-center justify-between gap-3 border-b border-gray-200/90 bg-white/82 px-4 py-3 backdrop-blur-lg">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($logoUrl && $logoUrl !== '')
                        <img src="{{ $logoUrl }}" alt="{{ $name }}" class="h-7 w-7 shrink-0 rounded-full object-cover">
                    @else
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[color-mix(in_srgb,var(--color-primary-500,#0BA284)_15%,white)] text-[var(--color-primary-700,#075748)]">
                            <x-heroicon-m-sparkles class="h-3.5 w-3.5" />
                        </div>
                    @endif

                    <div class="flex min-w-0 flex-col">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="text-[0.92rem] leading-none font-bold text-gray-900">{{ $name }}</span>

                            @if (! empty($pageContext))
                                <span
                                    class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[color-mix(in_srgb,var(--color-primary-500,#0BA284)_15%,white)] text-[var(--color-primary-700,#075748)]"
                                    title="{{ __('filament-chatbot::chatbot.page_context_tooltip') }}"
                                >
                                    <x-heroicon-m-link class="h-3 w-3" />
                                </span>
                            @endif
                        </div>
                        <span class="hidden truncate text-xs leading-5 text-gray-500 sm:block">{{ __('Ask a quick question or continue the conversation.') }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50/85 p-0.5">
                    <x-filament::icon-button
                        color="gray"
                        icon="heroicon-o-document"
                        wire:click="clearChat()"
                        :label="__('filament-chatbot::chatbot.new_session')"
                        :tooltip="__('filament-chatbot::chatbot.new_session')"
                    />
                    @if ($conversationId)
                        <x-filament::icon-button
                            color="gray"
                            icon="heroicon-o-arrows-pointing-out"
                            :href="\Wotz\FilamentChatbot\Filament\Resources\ConversationResource::getUrl('view', ['record' => $conversationId])"
                            tag="a"
                            x-on:click="$wire.panelHidden = true"
                            :label="__('filament-chatbot::chatbot.open_fullscreen')"
                            :tooltip="__('filament-chatbot::chatbot.open_fullscreen')"
                        />
                    @endif
                    @if ($showPositionBtn)
                        <x-filament::icon-button
                            color="gray"
                            icon="heroicon-s-arrows-right-left"
                            wire:click="changeWinPosition()"
                            :label="__('filament-chatbot::chatbot.move_window')"
                            :tooltip="__('filament-chatbot::chatbot.move_window')"
                        />
                    @endif
                    <x-filament::icon-button
                        color="gray"
                        icon="heroicon-o-x-mark"
                        wire:click="togglePanel"
                        :label="__('filament-chatbot::chatbot.close_chat')"
                        :tooltip="__('filament-chatbot::chatbot.close_chat')"
                    />
                </div>
            </div>

            @include('filament-chatbot::partials.chatbot-messages')
            @include('filament-chatbot::partials.chatbot-input')
        </div>
    @endif

    @script
        <script>
            const el = document.getElementById('chatbot-messages');

            Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                if (['chatbot-widget', 'chatbot-conversation'].includes(component.name)) {
                    succeed(({ snapshot, effect }) => {
                        setTimeout(() => {
                            if (el) { el.scrollTop = el.scrollHeight; }
                        }, 100);
                    });
                }
            });

            document.addEventListener('livewire:initialized', function () {
                const textarea = document.getElementById('chatbot-input');
                if (el) { el.scrollTop = el.scrollHeight; }
                if (textarea) { textarea.focus(); }
            });
        </script>
    @endscript
</div>
