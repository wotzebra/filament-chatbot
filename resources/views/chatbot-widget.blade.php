<div class="relative w-full" id="chatgpt-agent-window" style="{{ $winWidth }}">
    <div class="fixed z-30 cursor-pointer" style="bottom: 1rem; right: 1rem;">
        <x-filament::button wire:click="togglePanel" id="btn-chat" :icon="$buttonIcon" :color="$panelHidden ? 'primary' : 'gray'">
            {{ $panelHidden ? $buttonText : 'Sluiten' }}
        </x-filament::button>
    </div>

    <x-filament::section
        class="flex-1 p-2 sm:p-6 justify-between flex flex-col max-h-screen fixed {{ $winPosition == 'left' ? 'left-0' : 'right-0' }} bottom-0 bg-white shadow z-30 {{ $panelHidden ? 'hidden' : '' }}"
        style="{{ $winWidth }} {{ $winHeight }}" id="chat-window">
        <x-slot name="heading" :icon="$buttonIcon" icon-size="md">
            {{ $name }}
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::icon-button color="gray" icon="heroicon-o-document" wire:click="clearChat()" label="Nieuwe sessie" tooltip="Nieuwe sessie" />
            @if ($showPositionBtn)
                <x-filament::icon-button color="gray" icon="heroicon-s-arrows-right-left" wire:click="changeWinPosition()" label="Verplaats venster" tooltip="Verplaats venster" />
            @endif
            <x-filament::icon-button color="gray" icon="heroicon-s-minus-small" wire:click="togglePanel" label="Verberg chat" tooltip="Verberg chat" />
        </x-slot>

        <div id="messages" wire:scroll wire:key="chatbot-messages" style="overflow: auto; min-height: max(20rem, 30vh); max-height: calc(100% - 8rem); padding-bottom: 1rem; margin-bottom: 65px;" class="flex flex-col gap-6 overflow-y-auto scrollbar-thumb-blue scrollbar-thumb-rounded scrollbar-track-blue-lighter scrollbar-w-2 scrolling-touch px-2">
            @if ($messages->isEmpty() && ! $isStreaming)
                <div class="chat-message">
                    <div class="flex items-start gap-3">
                        @if($logoUrl && $logoUrl !== '')
                            <x-filament::avatar :src="$logoUrl" :alt="$name" size="sm" />
                        @else
                            <x-filament::avatar size="sm">
                                <x-heroicon-m-sparkles class="w-4 h-4" />
                            </x-filament::avatar>
                        @endif

                        <div class="flex-1 min-w-0">
                            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg px-4 py-2 text-sm break-words overflow-wrap-anywhere prose prose-sm dark:prose-invert max-w-none">
                                {!! \Illuminate\Mail\Markdown::parse($welcomeMessage) !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @foreach ($messages as $message)
                <div wire:key="chatbot-message-{{ $loop->index }}" class="flex flex-col gap-3">
                    @if ($message->role === \Laravel\Ai\Messages\MessageRole::User->value)
                        <div class="chat-message">
                            <div class="flex items-start justify-end gap-3">
                                <div class="flex-1 flex justify-end min-w-0">
                                    <div class="bg-blue-600 text-white rounded-lg px-4 py-2 text-sm break-words overflow-wrap-anywhere max-w-xs">
                                        {!! \Illuminate\Mail\Markdown::parse($message->content) !!}
                                    </div>
                                </div>

                                <x-filament::avatar size="sm" :src="auth()->user()?->avatar_url" :alt="auth()->user()?->name">
                                    <x-heroicon-m-user class="w-4 h-4" />
                                </x-filament::avatar>
                            </div>
                        </div>
                    @else
                        <div class="chat-message">
                            <div class="flex items-start gap-3">
                                @if($logoUrl && $logoUrl !== '')
                                    <x-filament::avatar :src="$logoUrl" :alt="$name" size="sm" />
                                @else
                                    <x-filament::avatar size="sm">
                                        <x-heroicon-m-sparkles class="w-4 h-4" />
                                    </x-filament::avatar>
                                @endif

                                <div class="flex-1 min-w-0">
                                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg px-4 py-2 text-sm break-words overflow-wrap-anywhere prose prose-sm dark:prose-invert max-w-none">
                                        {!! \Illuminate\Mail\Markdown::parse($message->content) !!}
                                    </div>
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
                                $wire.onStreamComplete(this.streamingText);
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
                }" x-init="init()" class="chat-message">
                    <div class="flex items-start gap-3">
                        @if($logoUrl && $logoUrl !== '')
                            <x-filament::avatar :src="$logoUrl" :alt="$name" size="sm" />
                        @else
                            <x-filament::avatar size="sm">
                                <x-heroicon-m-sparkles class="w-4 h-4" />
                            </x-filament::avatar>
                        @endif

                        <div class="flex-1 min-w-0">
                            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg px-4 py-2 text-sm break-words overflow-wrap-anywhere prose prose-sm dark:prose-invert max-w-none" x-html="streamingText || '<span class=\'text-gray-400\'>Aan het denken...</span>'"></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="fi-section-footer border-t border-gray-200 pt-4 dark:border-white/10 absolute bottom-0 left-0 p-2 sm:p-6 bg-white dark:bg-gray-900 w-full">
            <div class="relative">
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <x-filament::input.wrapper>
                            <textarea
                                x-data="{
                                    resize() {
                                        $el.style.height = '48px';
                                        $el.style.height = `${$el.scrollHeight}px`;
                                    },
                                    collapse() {
                                        $el.style.height = '48px';
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
                                style="max-height: 200px; height: 48px; resize: none;"
                                class="block w-full border-0 bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500 sm:text-sm sm:leading-6"
                                id="chat-input">
                            </textarea>
                        </x-filament::input.wrapper>
                    </div>

                    <div>
                        <x-filament::icon-button color="primary" icon="heroicon-o-paper-airplane" wire:click="askQuestion" :disabled="empty(trim($question))" tooltip="Verstuur bericht" />
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>

    <style>
        .scrollbar-w-2::-webkit-scrollbar {
            width: 0.5rem;
            height: 0.5rem;
        }

        .scrollbar-track-blue-lighter::-webkit-scrollbar-track {
            --bg-opacity: 1;
            background-color: #f7fafc;
            background-color: rgba(247, 250, 252, var(--bg-opacity));
        }

        .scrollbar-thumb-blue::-webkit-scrollbar-thumb {
            --bg-opacity: 1;
            background-color: #edf2f7;
            background-color: rgba(237, 242, 247, var(--bg-opacity));
        }

        .scrollbar-thumb-rounded::-webkit-scrollbar-thumb {
            border-radius: 0.25rem;
        }

        .bg-blue-600 {
            --tw-bg-opacity: 1;
            background-color: rgb(37 99 235 / var(--tw-bg-opacity));
        }

        .left-0 {
            left: 0;
        }

        .max-h-screen {
            max-height: 100vh;
        }

        .overflow-wrap-anywhere {
            overflow-wrap: anywhere;
        }
    </style>
@script
    <script>
        const el = document.getElementById('messages');

        Livewire.hook('message.processed', (message, component) => {
            if (component.fingerprint.name === 'chatbot-widget') {
                setTimeout(() => {
                    el.scrollTop = el.scrollHeight;
                }, 100);
            }
        });

        Livewire.hook('message.sent', (message, component) => {
            if (component.fingerprint.name === 'chatbot-widget') {
                setTimeout(() => {
                    el.scrollTop = el.scrollHeight;
                }, 50);
            }
        });

        document.addEventListener('livewire:initialized', function () {
            var textarea = document.querySelector('#chat-input');
            el.scrollTop = el.scrollHeight;
            textarea.focus();
            el.style.paddingBottom = `${textarea.scrollHeight}px`;
        });
    </script>
@endscript
</div>
