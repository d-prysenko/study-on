<?php

namespace App\Tests\Controller;

use App\DataFixtures\CourseFixtures;
use App\Service\BillingClient;
use App\Tests\AbstractTest;

class AuthenticationTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [new CourseFixtures()];
    }

    public function testLogin(): void
    {
        $client = static::getClient(false);

        $billingClientMock = $this->createMock(BillingClient::class);
        $billingClientMock
            ->method('authenticate')
            ->willReturn(static::$user);

        static::$client->getContainer()->set(
            BillingClient::class,
            $billingClientMock
        );

        $crawler = $client->request('GET', '/login');

        $form = $crawler->filter('#login_form')->form();

        $client->submit($form);

        // this must redirect us to /courses page
        $this->assertResponseRedirect();
        $this->assertEquals("/courses", $client->getResponse()->headers->get("location"));
    }
}
