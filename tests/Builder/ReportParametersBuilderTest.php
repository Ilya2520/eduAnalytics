<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Builder\Report\ReportParametersBuilder;
use App\DTO\Input\ReportCreateInputDTO;
use App\Service\CampaignMetricService;
use App\Service\MarketingCampaignService;
use PHPUnit\Framework\TestCase;

class ReportParametersBuilderTest extends TestCase
{
    public function testBuildParameters(): void
    {
        $metricService = $this->createMock(CampaignMetricService::class);
        $marketingService = $this->createMock(MarketingCampaignService::class);

        // Подготовим фейковые ответы сервисов
        $metricDto = new \App\DTO\Output\CampaignMetricOutputDTO(
            id: 1,
            campaignId: 10,
            recordDate: new \DateTimeImmutable('2024-01-01'),
            enrolledStudents: 5,
            totalApplications: 20,
            campaignBudget: 1000.0,
            advertisingCosts: 400.0,
            totalRevenue: 2500.0,
            costPerApplication: 20.0,
            costPerEnrolledStudent: 80.0,
            conversionRate: 0.25,
            roi: 1.5
        );
        $metricService->method('getMetricsByCampaign')->willReturn([$metricDto]);

        $campaignEntity = $this->getMockBuilder(\App\Entity\MarketingCampaign::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'getChannel', 'getStartDate', 'getEndDate'])
            ->getMock();
        $campaignEntity->method('getName')->willReturn('CampName');
        $campaignEntity->method('getChannel')->willReturn('email');
        $campaignEntity->method('getStartDate')->willReturn(new \DateTimeImmutable('2024-01-01'));
        $campaignEntity->method('getEndDate')->willReturn(new \DateTimeImmutable('2024-01-31'));
        $marketingService->method('getMarketingCampaignById')->willReturn($campaignEntity);

        $builder = new ReportParametersBuilder($metricService, $marketingService);

        // Сконструируем входной DTO
        $dto = new ReportCreateInputDTO();
        $dto->name = 'Test report';
        $dto->type = 'summary';
        $dto->parameters = new \App\DTO\Input\ReportParametersInputDTO();
        $dto->parameters->selectedCampaigns = [
            (function () {
                $o = new \stdClass();
                $o->campaignId = 10;
                $o->selectedMetrics = ['enrolled_students', 'roi'];
                return $o;
            })()
        ];
        $dto->parameters->reportFields = ['id', 'name'];
        $dto->parameters->startDate = new \DateTimeImmutable('2024-01-01');
        $dto->parameters->endDate = new \DateTimeImmutable('2024-01-31');

        $result = $builder->buildParameters($dto);

        $this->assertArrayHasKey('selectedCampaigns', $result);
        $this->assertSame('2024-01-01', $result['startDate']);
        $this->assertSame('2024-01-31', $result['endDate']);
        $this->assertIsArray($result['selectedCampaigns']);
        $this->assertSame(10, $result['selectedCampaigns'][0]['campaignId']);
        $this->assertArrayHasKey('metricValues', $result['selectedCampaigns'][0]);
        $this->assertSame('CampName', $result['selectedCampaigns'][0]['metricValues'][0]['name']);
        $this->assertArrayHasKey('enrolled_students', $result['selectedCampaigns'][0]['metricValues'][0]);
        $this->assertArrayHasKey('roi', $result['selectedCampaigns'][0]['metricValues'][0]);
    }
} 