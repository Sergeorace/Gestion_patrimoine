<?php

namespace App\Support;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Publication d'evenements metier vers RabbitMQ (topic exchange).
 */
class EventBus
{
    public function publish(string $event, array $payload): void
    {
        $conn = new AMQPStreamConnection(
            env('RABBITMQ_HOST', 'rabbitmq'),
            (int) env('RABBITMQ_PORT', 5672),
            env('RABBITMQ_USER', 'patrimoine'),
            env('RABBITMQ_PASSWORD', ''),
            env('RABBITMQ_VHOST', '/')
        );
        try {
            $channel = $conn->channel();
            $channel->exchange_declare('patrimoine.events', 'topic', false, true, false);
            $message = new AMQPMessage(json_encode([
                'event_id' => (string) \Illuminate\Support\Str::uuid(),
                'event' => $event,
                'occurred_at' => now()->toIso8601String(),
                'payload' => $payload,
            ]), ['delivery_mode' => 2, 'content_type' => 'application/json']);
            $channel->basic_publish($message, 'patrimoine.events', $event);
            $channel->close();
        } finally {
            $conn->close();
        }
    }
}
