<?php

namespace App\Notifications\Channels;

use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushChannel
{
    public function send($notifiable, $notification): void
    {
        $subscription = $notifiable->push_subscription;

        if (empty($subscription)) {
            return;
        }

        $payload = method_exists($notification, 'toPush')
            ? $notification->toPush($notifiable)
            : $notification->toDatabase($notifiable);

        $webPush = $this->webPush();
        $webPush->queueNotification(Subscription::create($subscription), json_encode($payload));

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                Log::warning('Push fallido', [
                    'endpoint' => $report->getEndpoint(),
                    'reason' => $report->getReason(),
                ]);
            }
        }
    }

    protected function webPush(): WebPush
    {
        return app(WebPush::class);
    }
}
