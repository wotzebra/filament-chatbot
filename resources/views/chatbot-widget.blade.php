<div id="chatgpt-agent-window">
    {{-- Toggle button: only visible when panel is hidden --}}
    <div class="fixed cursor-pointer {{ $panelHidden ? '' : 'hidden' }}" style="bottom: 1rem; right: 1rem; z-index: 40;">
        <x-filament::button wire:click="togglePanel" id="btn-chat" :icon="$buttonIcon" color="primary">
            {{ $buttonText }}
        </x-filament::button>
    </div>

    {{-- Chat window --}}
    <div
        class="fixed {{ $winPosition == 'left' ? 'left-0' : 'right-0' }} bottom-0 flex flex-col bg-white shadow-2xl {{ $panelHidden ? 'hidden' : '' }}"
        style="{{ $winWidth }} {{ $winHeight }}; z-index: 40; border-top-left-radius: 0.75rem; {{ $winPosition !== 'left' ? '' : 'border-top-right-radius: 0.75rem;' }} overflow: hidden; border: 1px solid rgb(229 231 235);"
        id="chat-window">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 shrink-0 bg-white">
            <div class="flex items-center gap-2.5">
                @if ($logoUrl && $logoUrl !== '')
                    <img src="{{ $logoUrl }}" alt="{{ $name }}" class="w-7 h-7 rounded-full object-cover shrink-0">
                @else
                    <div class="chatbot-bot-avatar">
                        <x-heroicon-m-sparkles class="w-3.5 h-3.5" />
                    </div>
                @endif
                <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ $name }}</span>
            </div>
            <div class="flex items-center gap-1">
                <x-filament::icon-button
                    color="gray"
                    icon="heroicon-o-document"
                    wire:click="clearChat()"
                    label="Nieuwe sessie"
                    tooltip="Nieuwe sessie"
                />
                @if ($showPositionBtn)
                    <x-filament::icon-button
                        color="gray"
                        icon="heroicon-s-arrows-right-left"
                        wire:click="changeWinPosition()"
                        label="Verplaats venster"
                        tooltip="Verplaats venster"
                    />
                @endif
                <x-filament::icon-button
                    color="gray"
                    icon="heroicon-o-x-mark"
                    wire:click="togglePanel"
                    label="Sluit chat"
                    tooltip="Sluit chat"
                />
            </div>
        </div>

        {{-- Messages --}}
        <div
            id="messages"
            wire:scroll
            wire:key="chatbot-messages"
            class="flex flex-col gap-4 flex-1 overflow-y-auto px-4 py-4">

            @if ($messages->isEmpty() && ! $isStreaming)
                <div class="flex items-start gap-2.5">
                    <div class="chatbot-bot-avatar mt-0.5">
                        <x-heroicon-m-sparkles class="w-3.5 h-3.5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="bg-gray-100 dark:bg-gray-800 rounded-2xl rounded-tl-sm px-4 py-2.5 text-sm break-words prose prose-sm dark:prose-invert max-w-none">
                            {!! \Illuminate\Mail\Markdown::parse($welcomeMessage) !!}
                        </div>
                    </div>
                </div>
            @endif

            @foreach ($messages as $message)
                <div wire:key="chatbot-message-{{ $loop->index }}">
                    @if ($message->role === \Laravel\Ai\Messages\MessageRole::User->value)
                        <div class="flex items-end justify-end gap-2">
                            <div style="max-width: 75%;">
                                <div class="bg-primary-600 text-white rounded-2xl rounded-br-sm px-4 py-2.5 text-sm break-words" style="overflow-wrap: anywhere;">
                                    {!! \Illuminate\Mail\Markdown::parse($message->content) !!}
                                </div>
                            </div>
                            <div class="chatbot-user-avatar shrink-0">
                                <x-heroicon-m-user class="w-3.5 h-3.5" />
                            </div>
                        </div>
                    @else
                        <div class="flex items-start gap-2.5">
                            @if ($logoUrl && $logoUrl !== '')
                                <img src="{{ $logoUrl }}" alt="{{ $name }}" class="w-7 h-7 rounded-full object-cover shrink-0 mt-0.5">
                            @else
                                <div class="chatbot-bot-avatar mt-0.5">
                                    <x-heroicon-m-sparkles class="w-3.5 h-3.5" />
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="bg-gray-100 dark:bg-gray-800 rounded-2xl rounded-tl-sm px-4 py-2.5 text-sm break-words prose prose-sm dark:prose-invert max-w-none" style="overflow-wrap: anywhere;">
                                    {!! \Illuminate\Mail\Markdown::parse($message->content) !!}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            @if ($isStreaming)
                <div x-data="{
                    streamingText: '',
                    init() {
                        const token = @js($streamToken);
                        const params = new URLSearchParams({
                            message: @js($streamMessage),
                            conversation_id: @js($conversationId),
                        });

                        const source = new EventSource(@js($streamRouteBase) + '/' + token + '?' + params.toString());
                        source.onmessage = (e) => {
                            if (e.data === '[DONE]') {
                                source.close();
                                $wire.onStreamComplete();
                                return;
                            }
                            try {
                                const event = JSON.parse(e.data);
                                if (event.type === 'text_delta') {
                                    this.streamingText += event.delta;
                                }
                            } catch (err) {}
                        };
                        source.onerror = () => {
                            source.close();
                            $wire.onStreamComplete();
                        };
                    }
                }" x-init="init()" class="flex items-start gap-2.5">
                    @if ($logoUrl && $logoUrl !== '')
                        <img src="{{ $logoUrl }}" alt="{{ $name }}" class="w-7 h-7 rounded-full object-cover shrink-0 mt-0.5">
                    @else
                        <div class="chatbot-bot-avatar mt-0.5">
                            <x-heroicon-m-sparkles class="w-3.5 h-3.5" />
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="bg-gray-100 dark:bg-gray-800 rounded-2xl rounded-tl-sm px-4 py-2.5 text-sm break-words prose prose-sm dark:prose-invert max-w-none" x-html="streamingText || '<span class=\'text-gray-400\'>Aan het denken...</span>'"></div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Input --}}
        <div class="border-t border-gray-200 dark:border-white/10 px-3 py-3 shrink-0 bg-white dark:bg-gray-900">
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <x-filament::input.wrapper>
                        <textarea
                            x-data="{
                                resize() {
                                    $el.style.height = '40px';
                                    $el.style.height = `${$el.scrollHeight}px`;
                                },
                                collapse() {
                                    $el.style.height = '40px';
                                }
                            }"
                            x-init="resize()"
                            @input="resize()"
                            @blur="collapse()"
                            @focus="resize()"
                            :disabled="$wire.isStreaming"
                            @keydown.enter="!$event.shiftKey && !$wire.isStreaming && ($event.preventDefault(), $wire.askQuestion())"
                            wire:model="question"
                            placeholder="Stuur een bericht..."
                            autofocus
                            style="max-height: 150px; height: 40px; resize: none;"
                            class="block w-full border-0 bg-transparent px-3 py-2 text-sm text-gray-950 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                            id="chat-input">
                        </textarea>
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <x-filament::icon-button
                        color="primary"
                        icon="heroicon-o-paper-airplane"
                        wire:click="askQuestion"
                        :disabled="empty(trim($question)) || $isStreaming"
                        tooltip="Verstuur bericht"
                    />
                </div>
            </div>
        </div>
    </div>

    <style>
        .chatbot-bot-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 9999px;
            background-color: color-mix(in srgb, var(--color-primary-500, #0BA284) 15%, white);
            color: var(--color-primary-600, #0A7B65);
            flex-shrink: 0;
        }

        .chatbot-user-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 9999px;
            background-color: rgb(229 231 235);
            color: rgb(107 114 128);
            flex-shrink: 0;
        }

        #messages::-webkit-scrollbar {
            width: 0.25rem;
        }

        #messages::-webkit-scrollbar-track {
            background-color: transparent;
        }

        #messages::-webkit-scrollbar-thumb {
            background-color: rgb(209 213 219);
            border-radius: 9999px;
        }
    </style>
</div>

@script
    <script>
        const el = document.getElementById('messages');

        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            if (component.name === 'chatbot-widget') {
                succeed(({ snapshot, effect }) => {
                    setTimeout(() => {
                        if (el) { el.scrollTop = el.scrollHeight; }
                    }, 100);
                });
            }
        });

        document.addEventListener('livewire:initialized', function () {
            const textarea = document.querySelector('#chat-input');
            if (el) { el.scrollTop = el.scrollHeight; }
            if (textarea) { textarea.focus(); }
        });
    </script>
@endscript