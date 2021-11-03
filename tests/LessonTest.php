<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\LessonFixtures;

class LessonTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [new CourseFixtures()];
    }

//    public function testSomething(): void
//    {
//        $client = AbstractTest::getClient();
//        $url = '/courses';
//
//        $crawler = $client->request('GET', $url);
//
////        $link = $crawler->selectLink('Подробнее')->link();
////        $crawler = $client->click($link);
//
//        $this->assertResponseOk();
//    }
}
