<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\Report;
use App\Event\ReportCreatedEvent;
use App\EventSubscriber\ReportEventSubscriber;
use App\Service\RabbitMQProducerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReportEventSubscriberTest extends TestCase
{
    public function testOnReportCreatedDispatchesMessage(): void
    {
        $producer = $this->createMock(RabbitMQProducerService::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/webhook/report/completed');

        $subscriber = new ReportEventSubscriber(
            producer: $producer,
            urlGenerator: $urlGenerator,
            logger: new NullLogger(),
            reportGenerationTopic: 'report.generate',
            reportWebhookRoute: 'webhook_report_completed',
        );

        $producer->expects($this->once())
            ->method('dispatchAsyncTaskWithWebhook')
            ->with(
                $this->equalTo('report.generate'),
                $this->callback(function (array $payload) {
                    return isset($payload['reportId']) && isset($payload['reportType']) && isset($payload['parameters']);
                }),
                $this->equalTo('/webhook/report/completed')
            );

        $report = $this->createMock(Report::class);
        $report->method('getId')->willReturn(42);
        $report->method('getName')->willReturn('Test');
        $report->method('getType')->willReturn('summary');
        $report->method('getRequestedBy')->willReturn(null);
        $report->method('getParameters')->willReturn(['k' => 'v']);

        $event = ReportCreatedEvent::fromEntity($report);

        $subscriber->onReportCreated($event);
    }
} 