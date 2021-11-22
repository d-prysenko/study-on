<?php

namespace App\Tests\Controller;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;

class CourseTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [new CourseFixtures()];
    }

    public function testCoursesAndLessonsPages(): void
    {
        $client = static::getClient();
        $em = self::getEntityManager();
        $lessonRep = $em->getRepository(Lesson::class);
        $courseRep = $em->getRepository(Course::class);
        $url = "/courses";

        $crawler = $client->request('GET', $url);

        // /courses page load
        $this->assertResponseOk();

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

        $lessonsCount = $crawler->filter('#lessons')->children()->count();

        $dbLessonsCount = $lessonRep->count(['course' => basename($link->getUri())]);

        // count of lessons on course page
        $this->assertEquals($dbLessonsCount, $lessonsCount);
    }

    public function testUniqueCourseCreation(): void
    {
        $client = static::getClient();

        $crawler = $client->request('GET', '/courses');

        $createCourseLink = $crawler->selectLink('Создать новый')->link();
        $crawler = $client->click($createCourseLink);

        $form = $crawler->selectButton('Сохранить')->form();

        $form->setValues([
            'course[code]' => 'math',
            'course[name]' => 'test course',
            'course[description]' => 'description of the course'
        ]);

        $client->submit($form);

        $this->assertResponseCode(422, $client->getResponse());
    }

    public function testCreationCourse(): void
    {
        // TODO: rename  all such pieces
        // TODO: separate tests
        // codecoverage, metrics, code quality, tdd
        $client = static::getClient();

        $crawler = $client->request('GET', '/courses');

        // open course creation page
        $createCourseLink = $crawler->filter('#create_course')->link();
        $crawler = $client->click($createCourseLink);

        $form = $crawler->filter('#course_form')->form();

        $courseName = 'test course';

        $form->setValues([
            'course[code]' => 'test',
            'course[name]' => $courseName,
            'course[description]' => 'description of the course'
        ]);

        $client->submit($form);

        // this must redirect us to /courses page
        $this->assertResponseRedirect();
    }

    public function testDeleteCourse(): void
    {
        $client = AbstractTest::getClient();

        $crawler = $client->request('GET', '/courses');

        $courses = $crawler->filter('#courses')->children();
        $coursesCount = $courses->count();
        $courseLink = $courses->first()->filter('a')->link();

        $client->click($courseLink);

        // TODO: click delete button and compare courses count
    }


    public function testPageIsNotFound(): void
    {
        $client = AbstractTest::getClient();
        $client->request('GET', '/not-found');

        $this->assertResponseNotFound();
    }
}
