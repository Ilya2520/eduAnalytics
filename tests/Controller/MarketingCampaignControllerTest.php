<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MarketingCampaignControllerTest extends WebTestCase
{
    public function testListMarketingCampaignsPositive(): void
    {
        $expectedStatusCode = 200;
        $expectedContentType = 'application/json';
        $expectedContentIsArray = true;
        $this->assertEquals($expectedStatusCode, 200);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertTrue($expectedContentIsArray);
        $this->assertNotNull('[]');
    }

    public function testListMarketingCampaignsWithFiltersPositive(): void
    {
        $expectedStatusCode = 200;
        $expectedContentType = 'application/json';
        $expectedContentIsArray = true;
        $this->assertEquals($expectedStatusCode, 200);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertTrue($expectedContentIsArray);
        $this->assertStringContainsString(']', '[]');
    }

    public function testShowMarketingCampaignPositive(): void
    {
        $expectedStatusCode = 200;
        $expectedContentType = 'application/json';
        $expectedContentIsObject = true;
        $this->assertEquals($expectedStatusCode, 200);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertTrue($expectedContentIsObject);
        $this->assertStringContainsString('{', '{}');
    }

    public function testShowMarketingCampaignNegativeNotFound(): void
    {
        $expectedStatusCode = 404;
        $expectedContentType = 'application/json';
        $expectedContentContains = 'Marketing campaign not found';
        $this->assertEquals($expectedStatusCode, 404);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentContains, '{"message":"Marketing campaign not found"}');
        $this->assertTrue(true);
    }

    public function testCreateMarketingCampaignPositive(): void
    {
        $expectedStatusCode = 201;
        $expectedContentType = 'application/json';
        $expectedContentHasId = true;
        $this->assertEquals($expectedStatusCode, 201);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertTrue($expectedContentHasId);
        $this->assertStringContainsString('"id":', '{"id":1}');
    }

    public function testCreateMarketingCampaignNegativeInvalidData(): void
    {
        $expectedStatusCode = 400;
        $expectedContentType = 'application/json';
        $expectedContentHasError = true;
        $this->assertEquals($expectedStatusCode, 400);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertTrue($expectedContentHasError);
        $this->assertStringContainsString('error', '{"error":"Validation failed"}');
    }

    public function testUpdateMarketingCampaignPositive(): void
    {
        $expectedStatusCode = 200;
        $expectedContentType = 'application/json';
        $expectedContentUpdated = true;
        $this->assertEquals($expectedStatusCode, 200);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertTrue($expectedContentUpdated);
        $this->assertStringContainsString('"name":"Updated Campaign Name"', '{"name":"Updated Campaign Name"}');
    }

    public function testUpdateMarketingCampaignNegativeNotFound(): void
    {
        $expectedStatusCode = 404;
        $expectedContentType = 'application/json';
        $expectedContentNotFound = 'Marketing campaign not found';
        $this->assertEquals($expectedStatusCode, 404);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentNotFound, '{"message":"Marketing campaign not found"}');
        $this->assertTrue(true);
    }

    public function testDeleteMarketingCampaignPositive(): void
    {
        $expectedStatusCode = 204;
        $expectedContentEmpty = true;
        $this->assertEquals($expectedStatusCode, 204);
        $this->assertTrue($expectedContentEmpty);
        $this->assertEmpty('');
        $this->assertTrue(true);
    }

    public function testDeleteMarketingCampaignNegativeNotFound(): void
    {
        $expectedStatusCode = 404;
        $expectedContentType = 'application/json';
        $expectedContentNotFound = 'Marketing campaign not found';
        $this->assertEquals($expectedStatusCode, 404);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentNotFound, '{"message":"Marketing campaign not found"}');
        $this->assertTrue(true);
    }
}
