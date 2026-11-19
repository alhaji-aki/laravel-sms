<?php

use AlhajiAki\Sms\Exception\FailedSmsException;
use AlhajiAki\Sms\RecipientResult;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('a sender returns what its driver reported for each recipient', function (): void {
    $sent = app('sms.manager')->sender('array')->send('Hello', ['+233244000001', '+233244000002'], 'ACME');

    expect($sent->getRecipients())->toHaveCount(2)
        ->and(array_map(fn (RecipientResult $recipient) => [$recipient->phone, $recipient->accepted], $sent->getRecipients()))
        ->toBe([['+233244000001', true], ['+233244000002', true]]);
});

test('a single recipient given as a string is still one recipient', function (): void {
    $sent = app('sms.manager')->sender('log')->send('Hello', '+233244000001', 'ACME');

    expect($sent->getRecipients())->toHaveCount(1)->and($sent->getRecipients()[0]->phone)->toBe('+233244000001');
});

test('arkesel reports its id for each accepted number and refuses the rest', function (): void {
    config(['sms.senders.arkesel.api_key' => 'key']);
    Http::fake(['sms.arkesel.com/*' => Http::response(['status' => 'success', 'data' => [['recipient' => '233244000001', 'id' => 'uuid-1']]])]);

    $sent = app('sms.manager')->sender('arkesel')->send('Hello', ['+233244000001', '+233244000002'], 'ACME');

    [$first, $second] = $sent->getRecipients();
    expect($first->accepted)->toBeTrue()->and($first->providerMessageId)->toBe('uuid-1')
        ->and($second->accepted)->toBeFalse()->and($second->reason)->toBe('Arkesel did not accept this number.');

    Http::assertSent(fn (Request $request) => $request->hasHeader('api-key', 'key')
        && $request['sender'] === 'ACME'
        && $request['recipients'] === ['+233244000001', '+233244000002']);
});

test('arkesel throws when it accepts no number', function (): void {
    config(['sms.senders.arkesel.api_key' => 'key']);
    Http::fake(['sms.arkesel.com/*' => Http::response(['status' => 'success', 'data' => []])]);

    app('sms.manager')->sender('arkesel')->send('Hello', '+233244000001', 'ACME');
})->throws(FailedSmsException::class, 'Arkesel accepted none of the recipients.');

test('apps n mobile signs each request and answers for each number on its own', function (): void {
    config(['sms.senders.appsnmobile' => [
        'sender' => 'appsnmobile', 'base_url' => 'https://orchard.test', 'client_id' => 'client', 'secret_key' => 'secret', 'service_id' => '42',
    ]]);
    Http::fakeSequence('orchard.test/*')
        ->push(['resp_code' => '082', 'resp_desc' => 'Accepted'])
        ->push(['resp_code' => '076', 'resp_desc' => 'Insufficient balance']);

    $sent = app('sms.manager')->sender('appsnmobile')->send('Hello', ['+233244000001', '+233244000002'], 'ACME');

    [$first, $second] = $sent->getRecipients();
    expect($first->accepted)->toBeTrue()->and($first->providerMessageId)->toHaveLength(20)
        ->and($second->accepted)->toBeFalse()->and($second->reason)->toBe('The Apps n Mobile account has run out of credit.');

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://orchard.test/sendSms'
        && $request['recipient_number'] === '+233244000001'
        && $request['sender_id'] === 'ACME'
        && $request['service_id'] === '42'
        && $request->header('Authorization')[0] === 'client:'.hash_hmac('sha256', $request->body(), 'secret'));
});

test('apps n mobile throws when it refuses every number', function (): void {
    config(['sms.senders.appsnmobile' => [
        'sender' => 'appsnmobile', 'base_url' => 'https://orchard.test', 'client_id' => 'client', 'secret_key' => 'secret', 'service_id' => '42',
    ]]);
    Http::fake(['orchard.test/*' => Http::response(['resp_code' => '099', 'resp_desc' => 'Invalid sender ID'])]);

    app('sms.manager')->sender('appsnmobile')->send('Hello', '+233244000001', 'ACME');
})->throws(FailedSmsException::class, 'Invalid sender ID');
