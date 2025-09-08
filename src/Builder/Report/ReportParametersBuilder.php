<?php

declare(strict_types=1);

namespace App\Builder\Report;

use App\DTO\Input\ReportCreateInputDTO;
use App\DTO\Output\CampaignMetricOutputDTO;
use App\Service\CampaignMetricService;
use App\Service\MarketingCampaignService;

class ReportParametersBuilder
{
    public function __construct(
        private readonly CampaignMetricService $metricService,
        private readonly MarketingCampaignService $marketingCampaignService,
    ) {
    }

    /**
     * Строит массив параметров отчёта из входного DTO, включая значения метрик.
     */
    public function buildParameters(ReportCreateInputDTO $dto): array
    {
        $parameters = [
            'selectedCampaigns' => array_map(function ($cdto) {
                return [
                    'campaignId' => $cdto->campaignId,
                    'selectedMetrics' => $cdto->selectedMetrics,
                    'metricValues' => $this->collectMetricValues($cdto->campaignId, $cdto->selectedMetrics),
                ];
            }, $dto->parameters->selectedCampaigns),
            'reportFields' => $dto->parameters->reportFields,
            'startDate' => $dto->parameters->startDate ? $dto->parameters->startDate->format('Y-m-d') : null,
            'endDate' => $dto->parameters->endDate ? $dto->parameters->endDate->format('Y-m-d') : null,
        ];

        return array_filter($parameters, static fn ($v) => $v !== null);
    }

    /**
     * Собирает значения метрик по выбранным ключам для кампании.
     */
    private function collectMetricValues(int $campaignId, array $selectedMetrics): array
    {
        $metrics = $this->metricService->getMetricsByCampaign($campaignId);
        $campaign = $this->marketingCampaignService->getMarketingCampaignById($campaignId);

        $result = [];
        /** @var CampaignMetricOutputDTO $metric */
        foreach ($metrics as $metric) {
            $row = [
                'name' => $campaign->getName(),
                'channel' => $campaign->getChannel(),
                'start' => $campaign->getStartDate()->format('Y-m-d'),
                'end' => $campaign->getEndDate()->format('Y-m-d'),
                'id' => $metric->id,
                'campaign_id' => $metric->campaignId,
                'record_date' => $metric->recordDate->format('Y-m-d'),
            ];

            foreach ($selectedMetrics as $metricName) {
                switch ($metricName) {
                    case 'enrolled_students':
                        $row['enrolled_students'] = $metric->enrolledStudents;
                        break;
                    case 'total_applications':
                        $row['total_applications'] = $metric->totalApplications;
                        break;
                    case 'campaign_budget':
                        $row['campaign_budget'] = $metric->campaignBudget;
                        break;
                    case 'advertising_costs':
                        $row['advertising_costs'] = $metric->advertisingCosts;
                        break;
                    case 'total_revenue':
                        $row['total_revenue'] = $metric->totalRevenue;
                        break;
                    case 'cost_per_application':
                        $row['cost_per_application'] = $metric->costPerApplication;
                        break;
                    case 'cost_per_enrolled':
                        $row['cost_per_enrolled'] = $metric->costPerEnrolledStudent;
                        break;
                    case 'conversion_rate':
                        $row['conversion_rate'] = $metric->conversionRate;
                        break;
                    case 'roi':
                        $row['roi'] = $metric->roi;
                        break;
                }
            }
            $result[] = $row;
        }

        return $result;
    }
} 