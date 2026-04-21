<div class="border-t border-gray-200/90 bg-white/88 {{ $standalone ? 'p-4' : 'p-3.5' }} backdrop-blur-lg">
    <div
        x-data="{
            submitting: false,
            async submit() {
                if (this.submitting || $wire.$get('isStreaming')) {
                    return;
                }

                const question = ($wire.$get('question') ?? '').trim();

                if (question === '') {
                    return;
                }

                this.submitting = true;

                try {
                    await $wire.askQuestion();
                } finally {
                    this.submitting = false;
                }
            }
        }"
        class="flex items-end gap-2.5 rounded-[1.15rem] bg-white px-1 py-1 shadow-[0_18px_35px_-30px_rgb(15_23_42/0.35)]"
    >
        <div class="min-w-0 flex-1">
            <div class="rounded-[1rem] bg-transparent">
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
                    x-bind:disabled="submitting || $wire.$get('isStreaming')"
                    @keydown.enter="!$event.shiftKey && ($event.preventDefault(), submit())"
                    wire:model.live="question"
                    placeholder="{{ __('filament-chatbot::chatbot.placeholder') }}"
                    autofocus
                    class="block max-h-[150px] w-full resize-none border-0 bg-transparent px-3 py-2 text-sm text-gray-950 outline-hidden placeholder:text-gray-400 disabled:cursor-progress"
                    style="height: 40px;"
                    id="chatbot-input">
                </textarea>
            </div>
        </div>

        <div class="flex items-end">
            <x-filament::icon-button
                color="primary"
                icon="heroicon-o-paper-airplane"
                wire:click="askQuestion"
                wire:loading.attr="disabled"
                wire:target="askQuestion"
                :disabled="empty(trim($question)) || $isStreaming"
                :tooltip="__('filament-chatbot::chatbot.send')"
            />
        </div>
    </div>
</div>
