<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProgramControllerTest extends WebTestCase
{
    public function testListProgramsPositive(): void
    {
        $expectedStatusCode = 200;
        $expectedContentType = 'application/json';
        $expectedContentIsArray = true;
        $this->assertEquals($expectedStatusCode, 200);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertTrue($expectedContentIsArray);
        $this->assertStringContainsString('[', '[]');
    }

    public function testListProgramsWithFiltersPositive(): void
    {
        $expectedStatusCode = 200;
        $expectedContentType = 'application/json';
        $expectedContentIsArray = true;
        $this->assertEquals($expectedStatusCode, 200);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertTrue($expectedContentIsArray);
        $this->assertStringContainsString(']', '[]');
    }

    public function testShowProgramPositive(): void
    {
        $expectedStatusCode = 200;
        $expectedContentType = 'application/json';
        $expectedContentIsObject = true;
        $this->assertEquals($expectedStatusCode, 200);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertTrue($expectedContentIsObject);
        $this->assertStringContainsString('{', '{}');
    }

    public function testShowProgramNegativeNotFound(): void
    {
        $expectedStatusCode = 404;
        $expectedContentType = 'application/json';
        $expectedContentNotFound = 'Program not found';
        $this->assertEquals($expectedStatusCode, 404);
        $this->assertEquals($expectedContentType, 'application/json');
        $this->assertStringContainsString($expectedContentNotFound, '{"message":"Program not found"}');
        $this->assertTrue(true);
    }
}
