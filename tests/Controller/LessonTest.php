<?php

namespace App\Tests\Controller;

use App\DataFixtures\CourseFixtures;
use App\Tests\AbstractTest;

class LessonTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [new CourseFixtures()];
    }

    public function testCreationLesson(): void
    {
        // TODO: rename  all such pieces
        // TODO: separate tests
        // codecoverage, metrics, code quality, tdd
        $client = static::getClient();

        $crawler = $client->request('GET', '/courses');

        // selecting our new course
        $courseLink = $crawler->selectLink("Базы данных")->link();
        $crawler = $client->click($courseLink);

        $this->assertResponseOk();

        // creating lesson in this course
        $createLessonLink = $crawler->filter('#create_lesson')->link();
        $crawler = $client->click($createLessonLink);

        $this->assertResponseOk();

        // create lesson form
        $form = $crawler->filter('#lesson_form')->form();

        $lessonTitle = 'This is new lesson!';

        $form->setValues([
            'lesson[title]' => $lessonTitle,
            'lesson[content]' => 'content of the lesson',
            'lesson[serial_number]' => 1
        ]);

        $client->submit($form);

        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();

        $lessonsCount = $crawler->filter('#lessons')->children()->count();

        $this->assertEquals(1, $lessonsCount);
    }
}