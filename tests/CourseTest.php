<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Repository\CourseRepository;

class CourseTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [new CourseFixtures()];
    }

    public function testSomething(): void
    {
        $client = AbstractTest::getClient();
        $em = self::getEntityManager();
        $lessonRep = $em->getRepository(Lesson::class);
        $courseRep = $em->getRepository(Course::class);
        $url = "/courses";

        $crawler = $client->request('GET', $url);

        // /courses page load
        $this->assertResponseOk();

        $coursesCount = $crawler->filter('#courses')->children()->count();
        $dbCoursesCount = $courseRep->count([]) ?? 1;

        // count of courses on main page
        // $this->assertEquals(min($dbCoursesCount, COUNT_COURSES_ON_PAGE_LIMIT), $coursesCount);
        $this->assertEquals($dbCoursesCount, $coursesCount);

        $course = $courseRep->findOneBy([]);
        $link = $crawler->selectLink($course->getName())->link();
        $crawler = $client->click($link);

        // loading page of some course
        $this->assertResponseOk();

        $lessonsCount = $crawler->filter('#lessons')->children()->count();

        $dbLessonsCount = $lessonRep->count(['course' => basename($link->getUri())]);

        // count of lessons on course page
        $this->assertEquals($dbLessonsCount, $lessonsCount);
    }

    public function testPageIsSuccessful()
    {
        $client = AbstractTest::getClient();
        $client->request('GET', '/courses');

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testPageIsNotFound()
    {
        $client = AbstractTest::getClient();
        $client->request('GET', '/not-found');

        $this->assertResponseNotFound();
    }
}
