<?php

namespace Wotz\FilamentChatbot\Livewire\Concerns;

use Livewire\Attributes\On;

trait HasPageContext
{
    /** @var array<string, mixed> */
    public array $pageContext = [];

    #[On('chatbot:context-updated')]
    public function setPageContext(array $context = []): void
    {
        $this->pageContext = $context;
    }
}
