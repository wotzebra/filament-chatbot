<?php

use Laravel\Ai\Messages\MessageRole;
use Livewire\Livewire;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Livewire\ChatbotWidget;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;

function conversationKey(): string
{
    return filament('chatbot')->getConversationKey();
}

it('defaults the conversation session key to the panel id', function () {
    $panelId = filament()->getCurrentPanel()->getId();

    expect(ChatbotPlugin::make()->getConversationKey())->toBe("ai_chatbot_conversation_id_{$panelId}");
});

it('mounts with empty state when the session is empty', function () {
    Livewire::test(ChatbotWidget::class)
        ->assertSet('conversationId', null)
        ->assertSet('isStreaming', false)
        ->assertSet('panelHidden', true);
});

it('loads existing messages when the session has a conversation id', function () {
    $conversation = AgentConversation::factory()->create();

    AgentConversationMessage::factory()->user()->for($conversation, 'conversation')->create();
    AgentConversationMessage::factory()->assistant()->for($conversation, 'conversation')->create();

    session()->put(conversationKey(), $conversation->id);

    $component = Livewire::test(ChatbotWidget::class)
        ->assertSet('conversationId', $conversation->id);

    $messages = $component->get('messages');

    expect($messages)->toHaveCount(2)
        ->and($messages[0]->role)->toBe(MessageRole::User->value)
        ->and($messages[1]->role)->toBe(MessageRole::Assistant->value);
});

it('ignores a session conversation id that no longer exists', function () {
    session()->put(conversationKey(), fake()->uuid());

    $component = Livewire::test(ChatbotWidget::class);

    expect($component->get('messages'))->toHaveCount(0);
});

it('restores panel hidden state from the session on mount', function () {
    session()->put('chatbot-panel-hidden', false);

    Livewire::test(ChatbotWidget::class)->assertSet('panelHidden', false);
});

it('restores the window position from the session on mount', function () {
    session()->put('chatbot-win-position', 'left');

    Livewire::test(ChatbotWidget::class)->assertSet('winPosition', 'left');
});

it('does nothing when askQuestion receives only whitespace', function () {
    Livewire::test(ChatbotWidget::class)
        ->set('question', '   ')
        ->call('askQuestion')
        ->assertSet('question', '')
        ->assertSet('isStreaming', false)
        ->assertSet('conversationId', null);

    expect(AgentConversation::count())->toBe(0);
});

it('pushes the user message and starts streaming on askQuestion', function () {
    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', $question = fake()->sentence())
        ->call('askQuestion')
        ->assertSet('question', '')
        ->assertSet('isStreaming', true)
        ->assertSet('streamMessage', $question);

    $messages = $component->get('messages');

    expect($messages)->toHaveCount(1)
        ->and($messages->first()->role)->toBe(MessageRole::User->value)
        ->and($messages->first()->content)->toBe($question);
});

it('creates a conversation record and stores its id in the session on the first question', function () {
    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', fake()->sentence())
        ->call('askQuestion');

    expect(AgentConversation::count())->toBe(1)
        ->and(session()->get(conversationKey()))->toBe($component->get('conversationId'));
});

it('reuses the same conversation on subsequent questions', function () {
    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', fake()->sentence())
        ->call('askQuestion');

    $firstConversationId = $component->get('conversationId');

    $component
        ->call('onStreamComplete', fake()->sentence())
        ->set('question', fake()->sentence())
        ->call('askQuestion');

    expect($component->get('conversationId'))->toBe($firstConversationId)
        ->and(AgentConversation::count())->toBe(1);
});

it('ignores new questions while a stream is already active', function () {
    Livewire::test(ChatbotWidget::class)
        ->set('isStreaming', true)
        ->set('question', $question = fake()->sentence())
        ->call('askQuestion')
        ->assertSet('question', $question)
        ->assertSet('streamMessage', '')
        ->assertSet('conversationId', null);

    expect(AgentConversation::count())->toBe(0);
});

it('adds an assistant message and clears stream state on onStreamComplete', function () {
    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', fake()->sentence())
        ->call('askQuestion')
        ->call('onStreamComplete', $reply = fake()->sentence())
        ->assertSet('isStreaming', false)
        ->assertSet('streamMessage', '');

    $messages = $component->get('messages');

    expect($messages->last()->role)->toBe(MessageRole::Assistant->value)
        ->and($messages->last()->content)->toBe($reply);
});

it('does not duplicate the assistant message when onStreamComplete is called twice', function () {
    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', fake()->sentence())
        ->call('askQuestion')
        ->call('onStreamComplete', $reply = fake()->sentence())
        ->call('onStreamComplete', $reply);

    $assistantMessages = $component->get('messages')
        ->filter(fn ($m) => $m->role === MessageRole::Assistant->value);

    expect($assistantMessages)->toHaveCount(1);
});

it('fetches the assistant message from the database when none is passed to onStreamComplete', function () {
    $conversation = AgentConversation::factory()->create();

    $message = AgentConversationMessage::factory()
        ->assistant()
        ->for($conversation, 'conversation')
        ->create();

    session()->put(conversationKey(), $conversation->id);

    $component = Livewire::test(ChatbotWidget::class)->call('onStreamComplete', '');

    expect($component->get('messages')->last()->content)->toBe($message->content);
});

it('persists a streamed fallback message when the stored assistant message is empty', function () {
    $conversation = AgentConversation::factory()->create();

    $emptyMessage = AgentConversationMessage::factory()
        ->assistant()
        ->for($conversation, 'conversation')
        ->create(['content' => '']);

    session()->put(conversationKey(), $conversation->id);

    $component = Livewire::test(ChatbotWidget::class)
        ->call('onStreamComplete', $reply = fake()->sentence());

    expect(AgentConversationMessage::query()->findOrFail($emptyMessage->id)->content)->toBe($reply)
        ->and($component->get('messages')->last()->content)->toBe($reply);
});

it('toggles the panel hidden state and persists it to the session', function () {
    $component = Livewire::test(ChatbotWidget::class)->assertSet('panelHidden', true);

    $component->call('togglePanel')->assertSet('panelHidden', false);
    expect(session()->get('chatbot-panel-hidden'))->toBeFalse();

    $component->call('togglePanel')->assertSet('panelHidden', true);
    expect(session()->get('chatbot-panel-hidden'))->toBeTrue();
});

it('toggles the window position between left and default and persists it to the session', function () {
    $component = Livewire::test(ChatbotWidget::class);

    $component->call('changeWinPosition')->assertSet('winPosition', 'left');
    expect(session()->get('chatbot-win-position'))->toBe('left');

    $component->call('changeWinPosition')->assertSet('winPosition', '');
    expect(session()->get('chatbot-win-position'))->toBe('');
});

it('mounts with an empty page context', function () {
    Livewire::test(ChatbotWidget::class)->assertSet('pageContext', []);
});

it('stores the page context when the chatbot:context-updated event is dispatched', function () {
    $context = ['type' => 'order', 'id' => 42];

    Livewire::test(ChatbotWidget::class)
        ->dispatch('chatbot:context-updated', context: $context)
        ->assertSet('pageContext', $context);
});

it('clears all chat state and removes the session key on clearChat', function () {
    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', fake()->sentence())
        ->call('askQuestion')
        ->call('clearChat')
        ->assertSet('conversationId', null)
        ->assertSet('isStreaming', false)
        ->assertSet('streamMessage', '');

    expect($component->get('messages'))->toHaveCount(0)
        ->and(session()->get(conversationKey()))->toBeNull();
});
