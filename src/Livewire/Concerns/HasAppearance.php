<?php

namespace Wotz\FilamentChatbot\Livewire\Concerns;

use Livewire\Attributes\Locked;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

trait HasAppearance
{
    #[Locked]
    public string|false $logoUrl;

    #[Locked]
    public string $name = '';

    #[Locked]
    public string $buttonText = '';

    #[Locked]
    public string $buttonIcon = '';

    #[Locked]
    public string $welcomeMessage = '';

    #[Locked]
    public string $winWidth = '';

    #[Locked]
    public string $winHeight = '';

    public string $winPosition = '';

    #[Locked]
    public bool $showPositionBtn = true;

    public bool $panelHidden = true;

    public function changeWinPosition(): void
    {
        $this->winPosition = $this->winPosition === 'left' ? '' : 'left';

        session()->put('chatbot-win-position', $this->winPosition);
    }

    public function togglePanel(): void
    {
        $this->panelHidden = ! $this->panelHidden;

        session()->put('chatbot-panel-hidden', $this->panelHidden);
    }

    protected function initializeAppearance(ChatbotPlugin $chatbot): void
    {
        $this->panelHidden = session()->get('chatbot-panel-hidden', true);
        $this->winPosition = session()->get('chatbot-win-position', '');
        $this->winWidth = 'width:' . $chatbot->getChatWidth() . ';';
        $this->winHeight = 'height:' . $chatbot->getChatHeight() . ';';
        $this->name = $chatbot->getBotName();
        $this->welcomeMessage = $chatbot->getWelcomeMessage();
        $this->buttonText = $chatbot->getButtonText();
        $this->buttonIcon = $chatbot->getButtonIcon();
        $this->logoUrl = $chatbot->getLogoUrl() ?? false;
    }
}