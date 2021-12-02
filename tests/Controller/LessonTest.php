<?php

namespace App\Tests\Controller;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;

class LessonTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [new CourseFixtures()];
    }

    public function testCreationLesson(): void
    {
        // codecoverage, metrics, code quality, tdd
        $client = static::getClient(true);

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
            'lesson[serialNumber]' => 1
        ]);

        $client->submit($form);

        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();

        $lessonsCount = $crawler->filter('#lessons')->children()->count();

        $this->assertEquals(3, $lessonsCount);
    }

    public function testDeleteLesson(): void
    {
        $client = static::getClient(true);

        $em = self::getEntityManager();
        $lessonRep = $em->getRepository(Lesson::class);
        $courseRep = $em->getRepository(Course::class);
        $url = "/courses";

        $crawler = $client->request('GET', $url);

        // /courses page load
        $this->assertResponseOk();
        //dd($client->getResponse());

        $coursesCount = $crawler->filter('#courses')->children()->count();
        $dbCoursesCount = $courseRep->count([]);

        // count of courses on main page
        // $this->assertEquals(min($dbCoursesCount, COUNT_COURSES_ON_PAGE_LIMIT), $coursesCount);
        $this->assertEquals($dbCoursesCount, $coursesCount);

        $course = $courseRep->findOneBy([]);
        $link = $crawler->selectLink($course->getName())->link();
        $crawler = $client->click($link);

        // loading page of some course
        $this->assertResponseOk();

        $lessons = $crawler->filter('#lessons')->children();
        $oldLessonsCount = $lessons->count();

        $lessonLink = $lessons->first()->children()->link();
        $crawler = $client->click($lessonLink);

        $deleteLessonButton = $crawler->selectButton("Удалить")->form();

        $client->click($deleteLessonButton);

        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();

        $lessonsCount = $crawler->filter('#lessons')->children()->count();

        $this->assertEquals($oldLessonsCount - 1, $lessonsCount);

    }
}