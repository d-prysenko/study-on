<?php

namespace App\Tests\Controller;

use App\DataFixtures\CourseFixtures;
use App\Tests\AbstractTest;

class CommonTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [new CourseFixtures()];
    }

    /**
     * @dataProvider pagesProvider
     */
    public function testUnauthenticated(string $method, string $url): void
    {
        $client = static::getClient(false);
        $client->request($method, $url);

        $this->assertResponseRedirect();
    }

    public function pagesProvider(): \Generator
    {
        yield ['GET', '/courses'];
        yield ['GET', '/profile'];
    }
}