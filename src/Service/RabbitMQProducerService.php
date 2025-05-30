<?php

namespace App\Service;

use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class RabbitMQProducerService
{
    public function __construct(
        private readonly ProducerInterface $producer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function dispatchAsyncTask(string $topic, $payload, array $properties = [], array $headers = []): void
    {
        $this->logger->debug('Dispatching async task', [
            'topic' => $topic,
            'payload' => $payload,
            'properties' => $properties,
            'headers' => $headers
        ]);

        try {
            $this->producer->sendEvent($topic, $payload, $properties, $headers);
            $this->logger->info('Successfully sent event to RabbitMQ', [
                'topic' => $topic
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send event to RabbitMQ', [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function dispatchAsyncTaskWithWebhook(
        string $topic,
        $taskPayload,
        string $webhookUrl,
        ?string $taskId = null,
        array $properties = [],
        array $headers = []
    ): string {
        $taskId = $taskId ?? Uuid::uuid4()->toString();

        $messagePayload = [
            'taskId' => $taskId,
            'taskData' => $taskPayload,
            'webhook' => [
                'url' => $webhookUrl,
                'method' => 'POST',
            ]
        ];

        $this->logger->debug('Dispatching async task with webhook', [
            'topic' => $topic,
            'taskId' => $taskId,
            'messagePayload' => $messagePayload,
            'properties' => $properties,
            'headers' => $headers,
            'webhookUrl' => $webhookUrl
        ]);

        try {
            // Создание сообщения с настройками
            $message = new Message();
            $message->setBody($messagePayload);

            // Добавление всех свойств и заголовков
            foreach ($properties as $name => $value) {
                $message->setProperty($name, $value);
            }

            foreach ($headers as $name => $value) {
                $message->setHeader($name, $value);
            }

            // Добавление диагностической информации
            $message->setProperty('_debug_sent_at', (new \DateTime())->format('Y-m-d H:i:s.u'));
            $message->setProperty('_debug_topic', $topic);

            // Отправка сообщения
            $this->producer->sendEvent($topic, $message);

            $this->logger->info('Successfully sent event with webhook to RabbitMQ', [
                'topic' => $topic,
                'taskId' => $taskId,
                'producerClass' => get_class($this->producer)
            ]);

            // Проверим информацию о топиках и очередях
            $this->logQueueInfo();

            return $taskId;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send event with webhook to RabbitMQ', [
                'topic' => $topic,
                'taskId' => $taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function logQueueInfo(): void
    {
        try {
            // Это не будет работать напрямую в PHP, но показывает, что вы можете
            // реализовать метод, который будет проверять состояние очередей
            // через RabbitMQ Management API или другой способ мониторинга
            $this->logger->debug('Queue configuration inspection', [
                'note' => 'This is a placeholder for queue inspection logic. You should implement actual queue checking.',
                'producerConfig' => [
                    'class' => get_class($this->producer),
                    // Если ProducerInterface имеет метод для получения конфигурации, вы можете их здесь вызвать
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to check queue info', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
