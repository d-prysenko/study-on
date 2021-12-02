<?php

namespace App\Tests\Controller;

use App\DataFixtures\CourseFixtures;
use App\Security\User;
use App\Service\BillingClient;
use App\Tests\AbstractTest;

class ProfileTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [new CourseFixtures()];
    }

    public function testProfilePage(): void
    {
        $client = static::getClient(true);

        $billingClientMock = $this->createMock(BillingClient::class);
        $billingClientMock
            ->method('getUser')
            ->willReturn(static::$user);

        static::$client->getContainer()->set(
            BillingClient::class,
            $billingClientMock
        );

        $crawler = $client->request('GET', '/profile');

        $this->assertResponseOk();

        $email = $crawler->filter('#email')->text();
        $balance = $crawler->filter('#balance')->text();

        $this->assertEquals(static::$user->getEmail(), $email);
        $this->assertEquals(static::$user->getBalance(), $balance);
    }

    public function testUnauthenticated(): void
    {
        $client = static::getClient(false);
        $client->request('GET', '/profile');

        $this->assertResponseRedirect();
    }
}