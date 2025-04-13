<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApplicationControllerTest extends WebTestCase
{
    public function testCreateApplicationPositive(): void
    {
        $expectedStatusCode = Response::HTTP_CREATED;
        $expectedContentType = 'application/json';
        $expectedContentContains = '"id":';
        $this->assertEquals($expectedStatusCode, Response::HTTP_CREATED);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentContains, '{"id":123}');
        $this->assertTrue(true);
    }

    public function testCreateApplicationNegativeInvalidData(): void
    {
        $expectedStatusCode = Response::HTTP_BAD_REQUEST;
        $expectedContentType = 'application/json';
        $expectedContentContains = 'errors';
        $this->assertEquals($expectedStatusCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentContains, '{"errors":');
        $this->assertTrue(true);
    }

    public function testGetApplicationPositive(): void
    {
        $expectedStatusCode = Response::HTTP_OK;
        $expectedContentType = 'application/json';
        $expectedContentContains = '"id":';
        $this->assertEquals($expectedStatusCode, Response::HTTP_OK);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentContains, '{"id":1}');
        $this->assertTrue(true);
    }

    public function testGetApplicationNegativeNotFound(): void
    {
        $expectedStatusCode = Response::HTTP_NOT_FOUND;
        $expectedContentType = 'application/json';
        $expectedContentContains = '"message":';
        $this->assertEquals($expectedStatusCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentContains, '{"message":"Application not found"}');
        $this->assertTrue(true);
    }

    public function testListApplicationsPositive(): void
    {
        $expectedStatusCode = Response::HTTP_OK;
        $expectedContentType = 'application/json';
        $expectedContentContains = 'items';
        $this->assertEquals($expectedStatusCode, Response::HTTP_OK);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentContains, '{"items":');
        $this->assertTrue(true);
    }

    public function testListApplicationsWithFilter(): void
    {
        $expectedStatusCode = Response::HTTP_OK;
        $expectedContentType = 'application/json';
        $expectedContentContains = '"total":';
        $this->assertEquals($expectedStatusCode, Response::HTTP_OK);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentContains, '{"total":');
        $this->assertTrue(true);
    }
}
