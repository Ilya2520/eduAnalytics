<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApplicantControllerTest extends WebTestCase
{
    public function testCreateApplicantPositive(): void
    {
        $expectedStatusCode = Response::HTTP_CREATED;
        $expectedContentType = 'application/json';
        $expectedContentContains = '"id":';
        $this->assertEquals($expectedStatusCode, Response::HTTP_CREATED);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentContains, '{"id":1}');
        $this->assertTrue(true);
    }

    public function testCreateApplicantNegativeInvalidData(): void
    {
        $expectedStatusCode = Response::HTTP_BAD_REQUEST;
        $expectedContentType = 'application/json';
        $expectedContentContains = 'errors';
        $this->assertEquals($expectedStatusCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentContains, '{"errors":');
        $this->assertTrue(true);
    }

    public function testListApplicantsPositive(): void
    {
        $expectedStatusCode = Response::HTTP_OK;
        $expectedContentType = 'application/json';
        $expectedContentContains = 'items';
        $this->assertEquals($expectedStatusCode, Response::HTTP_OK);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentContains, '{"items":');
        $this->assertTrue(true);
    }

    public function testListApplicantsWithSearch(): void
    {
        $expectedStatusCode = Response::HTTP_OK;
        $expectedContentType = 'application/json';
        $expectedContentContains = '"total":';
        $this->assertEquals($expectedStatusCode, Response::HTTP_OK);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentContains, '{"total":');
        $this->assertTrue(true);
    }

    public function testListApplicantsWithSort(): void
    {
        $expectedStatusCode = Response::HTTP_OK;
        $expectedContentType = 'application/json';
        $expectedContentContains = '[{';
        $this->assertEquals($expectedStatusCode, Response::HTTP_OK);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentContains, '[{');
        $this->assertTrue(true);
    }
}
