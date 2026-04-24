<div
    @if (! $standalone) id="filament-chatbot-window" @endif
    x-data="{ showConversationBrowser: false }"
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
                'fixed bottom-4 z-50 flex flex-col overflow-hidden rounded-xl border border-gray-200/90 bg-white/95 shadow-2xl backdrop-blur-sm',
                'left-4' => $winPosition === 'left',
                'right-4' => $winPosition !== 'left',
                'hidden' => $panelHidden,
            ])
            style="{{ $winWidth }} {{ $winHeight }} max-width: calc(100vw - 2rem); max-height: calc(100dvh - 5rem);"
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

            @php($activeConversation = $conversationId ? $this->conversations->get($conversationId) : null)

            <div class="border-b border-gray-200/80 bg-gray-50/70 px-3 py-1.5">
                <div class="flex items-center gap-2 overflow-x-auto">
                    <button
                        type="button"
                        wire:click="clearChat()"
                        @class([
                            'inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border transition',
                            'border-[color-mix(in_srgb,var(--color-primary-500,#0BA284)_25%,white)] bg-[color-mix(in_srgb,var(--color-primary-500,#0BA284)_10%,white)] text-[var(--color-primary-700,#075748)]' => $conversationId === null,
                            'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900' => $conversationId !== null,
                        ])
                        aria-label="{{ __('filament-chatbot::chatbot.new_conversation') }}"
                        title="{{ __('filament-chatbot::chatbot.new_conversation') }}"
                    >
                        <x-heroicon-m-plus class="h-3.5 w-3.5" />
                        <span class="sr-only">{{ __('filament-chatbot::chatbot.new_conversation') }}</span>
                    </button>

                    <button
                        type="button"
                        x-on:click="showConversationBrowser = true"
                        class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-700 transition hover:border-gray-300 hover:text-gray-900"
                        aria-label="{{ __('filament-chatbot::chatbot.browse_conversations') }}"
                        title="{{ __('filament-chatbot::chatbot.browse_conversations') }}"
                    >
                        <x-heroicon-m-queue-list class="h-3.5 w-3.5" />
                        <span class="sr-only">{{ __('filament-chatbot::chatbot.browse_conversations') }}</span>
                    </button>

                    @if ($activeConversation)
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[color-mix(in_srgb,var(--color-primary-500,#0BA284)_10%,white)] px-2.5 py-1 text-[11px] font-medium text-[var(--color-primary-700,#075748)]">
                            <span class="max-w-28 truncate">{{ $activeConversation->title !== '' ? $activeConversation->title : __('filament-chatbot::chatbot.conversation') }}</span>

                            @if ($isStreaming)
                                <span class="inline-flex h-2 w-2 rounded-full bg-[var(--color-primary-500,#0BA284)]"></span>
                            @endif
                        </span>
                    @endif

                    @foreach ($this->conversations->take(3) as $conversation)
                        @continue($conversation->id === $conversationId)

                        <button
                            type="button"
                            wire:click="openConversation('{{ $conversation->id }}')"
                            @class([
                                'inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-medium transition',
                                'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900',
                        ])
                    >
                            <span class="max-w-24 truncate">{{ $conversation->title !== '' ? $conversation->title : __('filament-chatbot::chatbot.conversation') }}</span>

                            @if (in_array($conversation->id, $this->streamingConversationIds, true))
                                <span class="inline-flex h-2 w-2 rounded-full bg-[var(--color-primary-500,#0BA284)]"></span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="relative flex min-h-0 flex-1">
                <div
                    x-cloak
                    x-show="showConversationBrowser"
                    x-transition.opacity
                    class="absolute inset-0 z-20 flex"
                >
                    <button
                        type="button"
                        class="absolute inset-0 bg-white/55 backdrop-blur-[1px]"
                        x-on:click="showConversationBrowser = false"
                        aria-label="{{ __('filament-chatbot::chatbot.close_conversations') }}"
                    ></button>

                    <aside class="relative flex h-full w-[calc(100%-0.75rem)] max-w-84 flex-col border-r border-gray-200/80 bg-white/98 shadow-[0_20px_40px_-32px_rgb(15_23_42/0.3)]">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-200/70 px-4 py-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-gray-900">{{ __('filament-chatbot::chatbot.conversations') }}</div>
                            </div>

                            <button
                                type="button"
                                class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                                x-on:click="showConversationBrowser = false"
                                aria-label="{{ __('filament-chatbot::chatbot.close_conversations') }}"
                            >
                                <x-heroicon-m-x-mark class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
                            <div class="space-y-1">
                                @foreach ($this->conversations as $conversation)
                                    <button
                                        type="button"
                                        wire:click="openConversation('{{ $conversation->id }}')"
                                        x-on:click="showConversationBrowser = false"
                                        @class([
                                            'flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left transition',
                                            'bg-[color-mix(in_srgb,var(--color-primary-500,#0BA284)_8%,white)] text-gray-900 ring-1 ring-[color-mix(in_srgb,var(--color-primary-500,#0BA284)_18%,white)]' => $conversation->id === $conversationId,
                                            'bg-white text-gray-700 hover:bg-gray-50 hover:text-gray-900' => $conversation->id !== $conversationId,
                                        ])
                                    >
                                        <span class="truncate text-[0.95rem] font-medium">{{ $conversation->title !== '' ? $conversation->title : __('filament-chatbot::chatbot.conversation') }}</span>

                                        <span class="flex shrink-0 items-center gap-1.5">
                                            @if (in_array($conversation->id, $this->streamingConversationIds, true))
                                                <span class="inline-flex h-2 w-2 rounded-full bg-(--color-primary-500,#0BA284)"></span>
                                            @endif
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </aside>
                </div>

                <div class="flex min-h-0 min-w-0 flex-1 flex-col">
                    @include('filament-chatbot::partials.chatbot-messages')
                    @include('filament-chatbot::partials.chatbot-input')
                </div>
            </div>
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

            window.addEventListener('chatbot-stream-cleanup', (event) => {
                const conversationId = event.detail?.conversationId;

                if (! conversationId || ! window.__filamentChatbotStreams) {
                    return;
                }

                Object.entries(window.__filamentChatbotStreams).forEach(([streamKey, stream]) => {
                    if (! streamKey.startsWith(`${conversationId}:`)) {
                        return;
                    }

                    if (typeof stream?.cleanup === 'function') {
                        stream.cleanup();
                    }

                    delete window.__filamentChatbotStreams[streamKey];
                });
            });
        </script>
    @endscript
</div>
