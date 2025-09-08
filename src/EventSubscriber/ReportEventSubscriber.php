<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\ReportCreatedEvent;
use App\Service\RabbitMQProducerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReportEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RabbitMQProducerService $producer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
        private readonly string $reportGenerationTopic,
        private readonly string $reportWebhookRoute,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportCreatedEvent::class => 'onReportCreated',
        ];
    }

    public function onReportCreated(ReportCreatedEvent $event): void
    {
        $webhookUrl = $this->urlGenerator->generate(
            $this->reportWebhookRoute,
            [],
            UrlGeneratorInterface::ABSOLUTE_PATH
        );

        $payload = [
            'reportId' => $event->reportId,
            'reportType' => $event->reportType,
            'parameters' => $event->parameters,
        ];

        try {
            $this->producer->dispatchAsyncTaskWithWebhook(
                $this->reportGenerationTopic,
                $payload,
                $webhookUrl
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to dispatch report generation from subscriber', [
                'reportId' => $event->reportId,
                'error' => $e->getMessage(),
            ]);
        }
    }
} 