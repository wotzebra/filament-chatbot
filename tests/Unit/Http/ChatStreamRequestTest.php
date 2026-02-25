<?php

use Wotz\FilamentChatbot\Http\Requests\ChatStreamRequest;

it('fails validation when message is missing', function () {
    $request = new ChatStreamRequest;
    $request->merge(['conversation_id' => fake()->uuid()]);

    $validator = validator($request->all(), $request->rules());

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->has('message'))->toBeTrue();
});

it('fails validation when conversation_id is missing', function () {
    $request = new ChatStreamRequest;
    $request->merge(['message' => fake()->sentence()]);

    $validator = validator($request->all(), $request->rules());

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->has('conversation_id'))->toBeTrue();
});

it('passes validation when both message and conversation_id are present', function () {
    $request = new ChatStreamRequest;
    $request->merge(['message' => fake()->sentence(), 'conversation_id' => fake()->uuid()]);

    expect(validator($request->all(), $request->rules())->passes())->toBeTrue();
});

it('trims whitespace from the message', function () {
    $request = new ChatStreamRequest;
    $request->merge(['message' => '  ' . ($message = fake()->sentence()) . '  ']);

    expect($request->message())->toBe($message);
});

it('returns the conversation id as a string', function () {
    $request = new ChatStreamRequest;
    $request->merge(['conversation_id' => $id = fake()->uuid()]);

    expect($request->conversationId())->toBe($id);
});
