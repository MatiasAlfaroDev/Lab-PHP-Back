<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_store_push_subscription(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => [
                'p256dh' => 'p256dh-key-value',
                'auth' => 'auth-key-value',
            ],
        ];

        $response = $this->postJson('/api/push/subscribe', $payload);

        $response->assertNoContent();
        $this->assertSame($payload, $user->refresh()->push_subscription);
    }

    public function test_subscribe_requires_endpoint_and_keys(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/push/subscribe', []);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_remove_push_subscription(): void
    {
        $user = User::factory()->create([
            'push_subscription' => [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
                'keys' => ['p256dh' => 'x', 'auth' => 'y'],
            ],
        ]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/push/subscribe');

        $response->assertNoContent();
        $this->assertNull($user->refresh()->push_subscription);
    }

    public function test_guest_cannot_manage_push_subscription(): void
    {
        $this->postJson('/api/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'x', 'auth' => 'y'],
        ])->assertUnauthorized();

        $this->deleteJson('/api/push/subscribe')->assertUnauthorized();
    }
}
