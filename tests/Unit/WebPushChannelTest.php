<?php

namespace Tests\Unit;

use App\Notifications\Channels\WebPushChannel;
use Minishlink\WebPush\SubscriptionInterface;
use Minishlink\WebPush\WebPush;
use Tests\TestCase;

class FakeWebPush extends WebPush
{
    public array $queued = [];

    public function __construct()
    {
    }

    public function queueNotification(SubscriptionInterface $subscription, ?string $payload = null, array $options = [], array $auth = []): void
    {
        $this->queued[] = [$subscription, $payload];
    }

    public function flush(?int $batchSize = null): \Generator
    {
        yield from [];
    }
}

class FakeNotifiable
{
    public function __construct(public ?array $push_subscription)
    {
    }
}

class FakeReservaNotification
{
    public function toPush($notifiable): array
    {
        return ['title' => 'Recordatorio', 'body' => 'Tenes una reserva'];
    }
}

class WebPushChannelTest extends TestCase
{
    public function test_does_nothing_when_notifiable_has_no_subscription(): void
    {
        $channel = new WebPushChannel();

        $channel->send(new FakeNotifiable(null), new FakeReservaNotification());

        $this->addToAssertionCount(1);
    }

    public function test_queues_and_flushes_push_when_subscription_exists(): void
    {
        $fakeWebPush = new FakeWebPush();
        $this->app->instance(WebPush::class, $fakeWebPush);

        $subscription = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'p256dh-value', 'auth' => 'auth-value'],
        ];

        (new WebPushChannel())->send(
            new FakeNotifiable($subscription),
            new FakeReservaNotification()
        );

        $this->assertCount(1, $fakeWebPush->queued);
        $this->assertSame(
            json_encode(['title' => 'Recordatorio', 'body' => 'Tenes una reserva']),
            $fakeWebPush->queued[0][1]
        );
    }
}
