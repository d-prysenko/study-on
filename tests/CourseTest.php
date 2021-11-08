<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Repository\CourseRepository;
use Symfony\Component\HttpFoundation\Response;

class CourseTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [new CourseFixtures()];
    }

    public function testCoursesAndLessonsPages(): void
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

    public function testUniqueCourseCreation()
    {
        $client = AbstractTest::getClient();

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

    public function testCreationCourseAndLesson()
    {
        $client = AbstractTest::getClient();

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
        $client->followRedirect();

        $crawler = $client->getCrawler();

        // selecting our new course
        $courseLink = $crawler->selectLink($courseName)->link();
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

    public function testDeleteCourse()
    {
        $client = AbstractTest::getClient();

        $crawler = $client->request('GET', '/courses');

        $courses = $crawler->filter('#courses')->children();
        $coursesCount = $courses->count();
        $courseLink = $courses->first()->filter('a')->link();

        $client->click($courseLink);

        // TODO: click delete button and compare courses count
    }


    public function testPageIsNotFound()
    {
        $client = AbstractTest::getClient();
        $client->request('GET', '/not-found');

        $this->assertResponseNotFound();
    }


}
