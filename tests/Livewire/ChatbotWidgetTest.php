<?php

use Illuminate\Support\Facades\Log;
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

function openConversationsKey(): string
{
    return conversationKey() . '_open';
}

function activeStreamsKey(): string
{
    return conversationKey() . '_active_streams';
}

it('defaults the conversation session key to the panel id', function () {
    $panelId = filament()->getCurrentPanel()->getId();

    expect(ChatbotPlugin::make()->getConversationKey())->toBe("ai_chatbot_conversation_id_{$panelId}");
});

it('mounts with empty state when the session is empty', function () {
    Log::spy();

    Livewire::test(ChatbotWidget::class)
        ->assertSet('conversationId', null)
        ->assertSet('openConversationIds', [])
        ->assertSet('isStreaming', false)
        ->assertSet('panelHidden', true);

    Log::shouldHaveReceived('debug')
        ->withArgs(fn (string $message, array $context): bool => $message === 'filament-chatbot.widget.mount'
            && $context['component'] === ChatbotWidget::class
            && $context['session_id'] === session()->getId())
        ->once();
});

it('loads existing messages when the session has a conversation id', function () {
    $conversation = AgentConversation::factory()->create();

    AgentConversationMessage::factory()->user()->for($conversation, 'conversation')->create();
    AgentConversationMessage::factory()->assistant()->for($conversation, 'conversation')->create();

    session()->put(conversationKey(), $conversation->id);
    session()->put(openConversationsKey(), [$conversation->id]);

    $component = Livewire::test(ChatbotWidget::class)
        ->assertSet('conversationId', $conversation->id);

    $messages = $component->get('messages');

    expect($messages)->toHaveCount(2)
        ->and($messages[0]['role'])->toBe(MessageRole::User->value)
        ->and($messages[1]['role'])->toBe(MessageRole::Assistant->value);
});

it('loads recent conversations into the sidebar list with previews', function () {
    $olderConversation = AgentConversation::factory()->create([
        'title' => 'Older thread',
        'updated_at' => now()->subHour(),
    ]);
    $newerConversation = AgentConversation::factory()->create([
        'title' => 'Newest thread',
        'updated_at' => now(),
    ]);

    AgentConversationMessage::factory()
        ->assistant()
        ->for($olderConversation, 'conversation')
        ->create(['content' => 'Older preview']);

    AgentConversationMessage::factory()
        ->assistant()
        ->for($newerConversation, 'conversation')
        ->create(['content' => 'Newest preview']);

    $conversationList = Livewire::test(ChatbotWidget::class)->get('conversationList');

    expect($conversationList)->toHaveCount(2)
        ->and($conversationList[0]['id'])->toBe($newerConversation->id)
        ->and($conversationList[0]['preview'])->toBe('Newest preview')
        ->and($conversationList[1]['id'])->toBe($olderConversation->id);
});

it('keeps the sidebar list sorted by updated at when opening another conversation', function () {
    $olderConversation = AgentConversation::factory()->create([
        'title' => 'Older thread',
        'updated_at' => now()->subHour(),
    ]);
    $newerConversation = AgentConversation::factory()->create([
        'title' => 'Newest thread',
        'updated_at' => now(),
    ]);

    AgentConversationMessage::factory()->assistant()->for($olderConversation, 'conversation')->create();
    AgentConversationMessage::factory()->assistant()->for($newerConversation, 'conversation')->create();

    $component = Livewire::test(ChatbotWidget::class);

    expect(array_column($component->get('conversationList'), 'id'))->toBe([
        $newerConversation->id,
        $olderConversation->id,
    ]);

    $component->call('openConversation', $olderConversation->id);

    expect(array_column($component->get('conversationList'), 'id'))->toBe([
        $newerConversation->id,
        $olderConversation->id,
    ]);
});

it('moves a conversation to the top of the sidebar list when it gets updated', function () {
    $olderConversation = AgentConversation::factory()->create([
        'title' => 'Older thread',
        'updated_at' => now()->subHour(),
    ]);
    $newerConversation = AgentConversation::factory()->create([
        'title' => 'Newest thread',
        'updated_at' => now()->subMinute(),
    ]);

    AgentConversationMessage::factory()->assistant()->for($olderConversation, 'conversation')->create();
    AgentConversationMessage::factory()->assistant()->for($newerConversation, 'conversation')->create();

    $component = Livewire::test(ChatbotWidget::class)
        ->call('openConversation', $olderConversation->id);

    expect(array_column($component->get('conversationList'), 'id'))->toBe([
        $newerConversation->id,
        $olderConversation->id,
    ]);

    $component
        ->set('question', fake()->sentence())
        ->call('askQuestion');

    expect(array_column($component->get('conversationList'), 'id'))->toBe([
        $olderConversation->id,
        $newerConversation->id,
    ]);
});

it('ignores a session conversation id that no longer exists', function () {
    session()->put(conversationKey(), fake()->uuid());

    $component = Livewire::test(ChatbotWidget::class);

    expect($component->get('conversationId'))->toBeNull()
        ->and($component->get('messages'))->toHaveCount(0)
        ->and(session()->get(conversationKey()))->toBe('');
});

it('ignores a session conversation id that is not owned by the current user', function () {
    $conversation = AgentConversation::factory()->create(['user_id' => 999]);

    session()->put(conversationKey(), $conversation->id);

    $component = Livewire::actingAs($this->makeTestUser())->test(ChatbotWidget::class);

    expect($component->get('conversationId'))->toBeNull()
        ->and($component->get('messages'))->toHaveCount(0)
        ->and(session()->get(conversationKey()))->toBe('');
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
        ->assertSet('shouldStartStreamRequest', true)
        ->assertSet('streamMessage', $question);

    $messages = $component->get('messages');

    expect($messages)->toHaveCount(1)
        ->and($messages[0]['role'])->toBe(MessageRole::User->value)
        ->and($messages[0]['content'])->toBe($question);
});

it('creates a conversation record and stores its id in the session on the first question', function () {
    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', fake()->sentence())
        ->call('askQuestion');

    expect(AgentConversation::count())->toBe(1)
        ->and(session()->get(conversationKey()))->toBe($component->get('conversationId'));
});

it('stores the active stream state in the session when asking a question', function () {
    $question = fake()->sentence();

    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', $question)
        ->call('askQuestion');

    expect(session()->get(activeStreamsKey()))->toBe([
        $component->get('conversationId') => ['message' => $question],
    ]);
});

it('stores the conversation in the open conversation list when asking a question', function () {
    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', fake()->sentence())
        ->call('askQuestion');

    expect(session()->get(openConversationsKey()))->toBe([$component->get('conversationId')]);
});

it('persists the user message and a pending assistant placeholder when asking a question', function () {
    $question = fake()->sentence();

    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', $question)
        ->call('askQuestion');

    $conversationId = $component->get('conversationId');
    $dbMessages = AgentConversationMessage::query()
        ->forConversation($conversationId)
        ->orderBy('created_at')
        ->get();

    expect($dbMessages)->toHaveCount(2)
        ->and($dbMessages[0]->role)->toBe(MessageRole::User->value)
        ->and($dbMessages[0]->content)->toBe($question)
        ->and($dbMessages[1]->role)->toBe(MessageRole::Assistant->value)
        ->and($dbMessages[1]->content)->toBe('')
        ->and($dbMessages[1]->meta['pending'] ?? null)->toBeTrue();
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

it('ignores a duplicate question when the session already has an active stream for the same conversation', function () {
    $conversation = AgentConversation::factory()->create();
    $question = fake()->sentence();

    AgentConversationMessage::factory()
        ->user()
        ->for($conversation, 'conversation')
        ->create(['content' => $question]);

    AgentConversationMessage::factory()
        ->assistant()
        ->for($conversation, 'conversation')
        ->create(['content' => '', 'meta' => ['pending' => true]]);

    $component = Livewire::test(ChatbotWidget::class);

    session()->put(conversationKey(), $conversation->id);
    session()->put(openConversationsKey(), [$conversation->id]);
    session()->put(activeStreamsKey(), [
        $conversation->id => ['message' => $question],
    ]);

    $component
        ->set('question', $question)
        ->call('askQuestion')
        ->assertSet('conversationId', $conversation->id)
        ->assertSet('isStreaming', true)
        ->assertSet('streamMessage', $question);

    expect(AgentConversationMessage::query()->forConversation($conversation->id)->count())->toBe(2);
});

it('adds an assistant message and clears stream state on onStreamComplete', function () {
    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', fake()->sentence())
        ->call('askQuestion')
        ->call('onStreamComplete', $reply = fake()->sentence())
        ->assertSet('isStreaming', false)
        ->assertSet('streamMessage', '');

    $messages = $component->get('messages');

    expect($messages[array_key_last($messages)]['role'])->toBe(MessageRole::Assistant->value)
        ->and($messages[array_key_last($messages)]['content'])->toBe($reply);
});

it('forgets the active stream state when the stream completes', function () {
    Livewire::test(ChatbotWidget::class)
        ->set('question', fake()->sentence())
        ->call('askQuestion')
        ->call('onStreamComplete', fake()->sentence());

    expect(session()->get(activeStreamsKey()))->toBe([]);
});

it('does not duplicate the assistant message when onStreamComplete is called twice', function () {
    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', fake()->sentence())
        ->call('askQuestion')
        ->call('onStreamComplete', $reply = fake()->sentence())
        ->call('onStreamComplete', $reply);

    $assistantMessages = collect($component->get('messages'))
        ->filter(fn ($message) => $message['role'] === MessageRole::Assistant->value);

    expect($assistantMessages)->toHaveCount(1);
});

it('fetches the assistant message from the database when none is passed to onStreamComplete', function () {
    $conversation = AgentConversation::factory()->create();

    $message = AgentConversationMessage::factory()
        ->assistant()
        ->for($conversation, 'conversation')
        ->create();

    session()->put(conversationKey(), $conversation->id);
    session()->put(openConversationsKey(), [$conversation->id]);

    $component = Livewire::test(ChatbotWidget::class)->call('onStreamComplete', '');

    $messages = $component->get('messages');

    expect($messages[array_key_last($messages)]['content'])->toBe($message['content']);
});

it('persists a streamed fallback message when the stored assistant message is empty', function () {
    $conversation = AgentConversation::factory()->create();

    $emptyMessage = AgentConversationMessage::factory()
        ->assistant()
        ->for($conversation, 'conversation')
        ->create(['content' => '']);

    session()->put(conversationKey(), $conversation->id);
    session()->put(openConversationsKey(), [$conversation->id]);

    $component = Livewire::test(ChatbotWidget::class)
        ->call('onStreamComplete', $reply = fake()->sentence());

    $messages = $component->get('messages');

    expect(AgentConversationMessage::query()->findOrFail($emptyMessage->id)['content'])->toBe($reply)
        ->and($messages[array_key_last($messages)]['content'])->toBe($reply);
});

it('keeps messages serializable after loading history and asking a new question', function () {
    $conversation = AgentConversation::factory()->create();

    AgentConversationMessage::factory()->user()->for($conversation, 'conversation')->create();
    AgentConversationMessage::factory()->assistant()->for($conversation, 'conversation')->create();

    session()->put(conversationKey(), $conversation->id);

    Livewire::test(ChatbotWidget::class)
        ->set('question', $question = fake()->sentence())
        ->call('askQuestion')
        ->assertSet('isStreaming', true)
        ->assertSet('streamMessage', $question);
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

it('renders compact conversation browser controls for the floating widget', function () {
    Livewire::test(ChatbotWidget::class)
        ->assertSeeInOrder([
            __('filament-chatbot::chatbot.new_conversation'),
            __('filament-chatbot::chatbot.browse_conversations'),
        ], false)
        ->assertSee('showConversationBrowser', false);
});

it('renders a client-side submit lock for the chat input', function () {
    Livewire::test(ChatbotWidget::class)
        ->assertSee('submitting: false', false)
        ->assertSee("if (this.submitting || \$wire.\$get('isStreaming')) {", false)
        ->assertSee('@keydown.enter="!$event.shiftKey && ($event.preventDefault(), submit())"', false);
});

it('renders the conversation browser list with titles only', function () {
    $conversation = AgentConversation::factory()->create([
        'title' => 'Title only thread',
    ]);

    AgentConversationMessage::factory()
        ->assistant()
        ->for($conversation, 'conversation')
        ->create(['content' => 'This preview should stay out of the browser list']);

    Livewire::test(ChatbotWidget::class)
        ->assertSee('Title only thread')
        ->assertDontSee('This preview should stay out of the browser list')
        ->assertDontSee(__('filament-chatbot::chatbot.new_conversation_hint'));
});

it('stores the page context when the chatbot:context-updated event is dispatched', function () {
    $context = ['type' => 'order', 'id' => 42];

    Livewire::test(ChatbotWidget::class)
        ->dispatch('chatbot:context-updated', context: $context)
        ->assertSet('pageContext', $context);
});

it('mounts in standalone mode when a conversation id is provided', function () {
    $user = $this->makeTestUser();
    $conversation = AgentConversation::factory()->create(['user_id' => $user->id]);

    AgentConversationMessage::factory()->user()->for($conversation, 'conversation')->create();
    AgentConversationMessage::factory()->assistant()->for($conversation, 'conversation')->create();

    $component = Livewire::actingAs($user)->test(ChatbotWidget::class, ['conversationId' => $conversation->id])
        ->assertSet('standalone', true)
        ->assertSet('conversationId', $conversation->id)
        ->assertSet('isStreaming', false);

    expect($component->get('messages'))->toHaveCount(2);
});

it('restores an active stream across navigation and keeps listening on the same conversation', function () {
    $conversation = AgentConversation::factory()->create();
    $question = fake()->sentence();

    AgentConversationMessage::factory()
        ->user()
        ->for($conversation, 'conversation')
        ->create(['content' => $question]);

    AgentConversationMessage::factory()
        ->assistant()
        ->for($conversation, 'conversation')
        ->create(['content' => '', 'meta' => ['pending' => true]]);

    session()->put(conversationKey(), $conversation->id);
    session()->put(openConversationsKey(), [$conversation->id]);
    session()->put(activeStreamsKey(), [
        $conversation->id => ['message' => $question],
    ]);

    $component = Livewire::test(ChatbotWidget::class)
        ->assertSet('conversationId', $conversation->id)
        ->assertSet('isStreaming', true)
        ->assertSet('shouldStartStreamRequest', false)
        ->assertSet('streamMessage', $question)
        ->assertSet('initialStreamingText', '');

    $messages = $component->get('messages');

    expect($messages)->toHaveCount(1)
        ->and($messages[0]['role'])->toBe(MessageRole::User->value)
        ->and($messages[0]['content'])->toBe($question);
});

it('does not duplicate the pending user message when restoring an active stream with persisted history', function () {
    $conversation = AgentConversation::factory()->create();
    $question = fake()->sentence();

    AgentConversationMessage::factory()
        ->user()
        ->for($conversation, 'conversation')
        ->create(['content' => $question]);

    AgentConversationMessage::factory()
        ->assistant()
        ->for($conversation, 'conversation')
        ->create(['content' => '', 'meta' => ['pending' => true]]);

    session()->put(conversationKey(), $conversation->id);
    session()->put(openConversationsKey(), [$conversation->id]);
    session()->put(activeStreamsKey(), [
        $conversation->id => ['message' => $question],
    ]);

    $component = Livewire::test(ChatbotWidget::class)
        ->assertSet('isStreaming', true)
        ->assertSet('streamMessage', $question);

    expect($component->get('messages'))->toHaveCount(1);
});

it('restores the partial assistant response into the streaming bubble across navigation', function () {
    $conversation = AgentConversation::factory()->create();
    $question = fake()->sentence();
    $partialAnswer = fake()->sentence();

    AgentConversationMessage::factory()
        ->user()
        ->for($conversation, 'conversation')
        ->create(['content' => $question]);

    AgentConversationMessage::factory()
        ->assistant()
        ->for($conversation, 'conversation')
        ->create(['content' => $partialAnswer, 'meta' => ['pending' => true]]);

    session()->put(conversationKey(), $conversation->id);
    session()->put(openConversationsKey(), [$conversation->id]);
    session()->put(activeStreamsKey(), [
        $conversation->id => ['message' => $question],
    ]);

    $component = Livewire::test(ChatbotWidget::class)
        ->assertSet('isStreaming', true)
        ->assertSet('streamMessage', $question)
        ->assertSet('initialStreamingText', $partialAnswer);

    $messages = $component->get('messages');

    expect($messages)->toHaveCount(1)
        ->and($messages[0]['role'])->toBe(MessageRole::User->value)
        ->and($messages[0]['content'])->toBe($question);
});

it('renders streaming takeover logic so a remounted widget can replace the previous listener', function () {
    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', fake()->sentence())
        ->call('askQuestion')
        ->assertSet('isStreaming', true);

    $component
        ->assertSee('const existingStream = window.__filamentChatbotStreams[this.streamKey];', false)
        ->assertSee("existingStream && typeof existingStream.cleanup === 'function'", false)
        ->assertSee('existingStream.cleanup();', false)
        ->assertSee('seenEventIds: [],', false)
        ->assertSee("if (typeof event.id === 'string' && this.seenEventIds.includes(event.id)) {", false)
        ->assertSee('shouldStartStreamRequest: true,', false)
        ->assertSee("window.__filamentChatbotStreams[this.streamKey] = {\n                    cleanup: () => this.cleanup(),", false);
});

it('renders restored streams without restarting the websocket request', function () {
    $conversation = AgentConversation::factory()->create();
    $question = fake()->sentence();

    AgentConversationMessage::factory()
        ->user()
        ->for($conversation, 'conversation')
        ->create(['content' => $question]);

    AgentConversationMessage::factory()
        ->assistant()
        ->for($conversation, 'conversation')
        ->create(['content' => '', 'meta' => ['pending' => true]]);

    session()->put(conversationKey(), $conversation->id);
    session()->put(openConversationsKey(), [$conversation->id]);
    session()->put(activeStreamsKey(), [
        $conversation->id => ['message' => $question],
    ]);

    Livewire::test(ChatbotWidget::class)
        ->assertSee('shouldStartStreamRequest: false,', false)
        ->assertSee('debugEnabled: true,', false)
        ->assertSee('if (! this.shouldStartStreamRequest) {', false);
});

it('drops an invalid active stream from the session on mount', function () {
    session()->put(activeStreamsKey(), [
        fake()->uuid() => ['message' => fake()->sentence()],
    ]);

    Livewire::test(ChatbotWidget::class)
        ->assertSet('conversationId', null)
        ->assertSet('isStreaming', false);

    expect(session()->get(activeStreamsKey()))->toBe([]);
});

it('renders the widget view without errors in standalone mode', function () {
    $user = $this->makeTestUser();
    $conversation = AgentConversation::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)->test(ChatbotWidget::class, ['conversationId' => $conversation->id])
        ->assertOk()
        ->assertSee('chatbot-input', false);
});

it('aborts with 403 when accessing a standalone conversation owned by another user', function () {
    $conversation = AgentConversation::factory()->create(['user_id' => 999]);
    $intruder = $this->makeTestUser();

    Livewire::actingAs($intruder)->test(ChatbotWidget::class, ['conversationId' => $conversation->id])
        ->assertForbidden();
});

it('starts a new draft session without removing the existing conversation', function () {
    $component = Livewire::test(ChatbotWidget::class)
        ->set('question', fake()->sentence())
        ->call('askQuestion')
        ->call('clearChat')
        ->assertSet('conversationId', null)
        ->assertSet('isStreaming', false)
        ->assertSet('streamMessage', '');

    expect($component->get('messages'))->toHaveCount(0)
        ->and(session()->get(conversationKey()))->toBe('')
        ->and(session()->get(openConversationsKey()))->toHaveCount(1);
});

it('can keep a streaming conversation open while starting a second conversation', function () {
    $firstComponent = Livewire::test(ChatbotWidget::class)
        ->set('question', $firstQuestion = fake()->sentence())
        ->call('askQuestion')
        ->assertSet('isStreaming', true);

    $firstConversationId = $firstComponent->get('conversationId');

    $firstComponent
        ->call('clearChat')
        ->set('question', $secondQuestion = fake()->sentence())
        ->call('askQuestion')
        ->assertSet('isStreaming', true);

    $secondConversationId = $firstComponent->get('conversationId');

    expect($secondConversationId)->not->toBe($firstConversationId)
        ->and(session()->get(openConversationsKey()))->toBe([$secondConversationId, $firstConversationId])
        ->and(session()->get(activeStreamsKey()))->toBe([
            $firstConversationId => ['message' => $firstQuestion],
            $secondConversationId => ['message' => $secondQuestion],
        ]);
});

it('can switch back to an earlier conversation while another one is open', function () {
    $firstConversation = AgentConversation::factory()->create(['title' => 'First']);
    $secondConversation = AgentConversation::factory()->create(['title' => 'Second']);

    AgentConversationMessage::factory()->user()->for($firstConversation, 'conversation')->create(['content' => 'First question']);
    AgentConversationMessage::factory()->assistant()->for($firstConversation, 'conversation')->create(['content' => 'First answer']);
    AgentConversationMessage::factory()->user()->for($secondConversation, 'conversation')->create(['content' => 'Second question']);
    AgentConversationMessage::factory()->assistant()->for($secondConversation, 'conversation')->create(['content' => 'Second answer']);

    session()->put(conversationKey(), $secondConversation->id);
    session()->put(openConversationsKey(), [$secondConversation->id, $firstConversation->id]);

    Livewire::test(ChatbotWidget::class)
        ->call('openConversation', $firstConversation->id)
        ->assertSet('conversationId', $firstConversation->id)
        ->assertSet('messages.0.content', 'First question')
        ->assertSet('messages.1.content', 'First answer');
});

it('can open an owned conversation that is not in the open session list', function () {
    $conversation = AgentConversation::factory()->create(['title' => 'Archived']);

    AgentConversationMessage::factory()->user()->for($conversation, 'conversation')->create(['content' => 'Archived question']);
    AgentConversationMessage::factory()->assistant()->for($conversation, 'conversation')->create(['content' => 'Archived answer']);

    Livewire::test(ChatbotWidget::class)
        ->call('openConversation', $conversation->id)
        ->assertSet('conversationId', $conversation->id)
        ->assertSet('messages.0.content', 'Archived question')
        ->assertSet('messages.1.content', 'Archived answer');

    expect(session()->get(openConversationsKey()))->toContain($conversation->id);
});
