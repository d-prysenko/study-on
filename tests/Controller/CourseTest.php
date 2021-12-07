<?php

namespace App\Tests\Controller;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Service\BillingClient;
use App\Tests\AbstractTest;

class CourseTest extends AbstractTest
{
        protected function getFixtures(): array
    {
        return [new CourseFixtures()];
    }

    public function testCoursesAndLessonsPages(): void
    {
        $client = static::getClient(true);

        $billingClientMock = $this->createMock(BillingClient::class);
        $billingClientMock
            ->method('getCourses')
            ->willReturn(static::$apiCoursesInfo);

        static::$client->getContainer()->set(
            BillingClient::class,
            $billingClientMock
        );

        $em = static::getEntityManager();
        $lessonRep = $em->getRepository(Lesson::class);
        $courseRep = $em->getRepository(Course::class);

        $crawler = $client->request('GET', "/courses");
        $this->assertResponseCode(200, $client->getResponse());

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
        $client = static::getClient(true);

        $billingClientMock = $this->createMock(BillingClient::class);
        $billingClientMock
            ->method('getCourses')
            ->willReturn(static::$apiCoursesInfo);

        static::$client->getContainer()->set(
            BillingClient::class,
            $billingClientMock
        );

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseCode(200, $client->getResponse());

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

//    public function testAccessDenied(): void
//    {
//        $client = static::getClient(true, ['ROLE_USER']);
//
//        $billingClientMock = $this->createMock(BillingClient::class);
//        $billingClientMock
//            ->method('getCourses')
//            ->willReturn(static::$apiCoursesInfo)
//        ;
//
//        static::$client->getContainer()->set(
//            BillingClient::class,
//            $billingClientMock
//        );
//
//        $em = static::getEntityManager();
//        $courseRep = $em->getRepository(Course::class);
//
//        $crawler = $client->request('GET', "/courses");
//        $this->assertResponseCode(200, $client->getResponse());
//
//        $course = $courseRep->findOneBy([]);
//        $link = $crawler->selectLink($course->getName())->link();
//        $crawler = $client->click($link);
//
//        dd($crawler);
//    }

    public function testCreationCourse(): void
    {
        $client = static::getClient(true);

        $billingClientMock = $this->createMock(BillingClient::class);
        $billingClientMock
            ->method('getCourses')
            ->willReturn(static::$apiCoursesInfo);

        static::$client->getContainer()->set(
            BillingClient::class,
            $billingClientMock
        );

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseCode(200, $client->getResponse());

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
        $client = static::getClient(true);

        $billingClientMock = $this->createMock(BillingClient::class);
        $billingClientMock
            ->method('getCourses')
            ->willReturn(static::$apiCoursesInfo);

        static::$client->getContainer()->set(
            BillingClient::class,
            $billingClientMock
        );

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseCode(200, $client->getResponse());

        $courses = $crawler->filter('#courses')->children();
        $coursesCount = $courses->count();
        $courseLink = $courses->first()->filter('a')->link();

        $crawler = $client->click($courseLink);
        $deleteCourseButton = $crawler->selectButton("Удалить")->form();

        $client->click($deleteCourseButton);

        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();

        $coursesCount = $crawler->filter('#courses')->children()->count();

        $this->assertEquals(1, $coursesCount);
    }

    public function testUnauthenticated(): void
    {
        $client = static::getClient(false);
        $client->request('GET', '/courses');

        $this->assertResponseRedirect();
    }

    public function testPageIsNotFound(): void
    {
        $client = static::getClient(true);
        $client->request('GET', '/not-found');

        $this->assertResponseNotFound();
    }
}
