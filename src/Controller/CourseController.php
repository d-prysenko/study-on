<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CourseController extends AbstractController
{
    public function index(CourseRepository $courseRepository, BillingClient $billingClient): Response
    {
        return $this->render('course/index.html.twig', [
            'courses' => $courseRepository->findAll(),
            'course_info' => $billingClient->getCourses(true),
            'my_courses' => $billingClient->getUserCourses(true),
        ]);
    }

    public function myCourses(CourseRepository $courseRepository, BillingClient $billingClient): Response
    {
        $courses = $billingClient->getUserCourses(true);

        $myCourses = [];
        foreach ($courses as $course) {
            $myCourses[] = $courseRepository->findOneBy(['code' => $course['code']]);
        }

        return $this->render('course/my.html.twig', [
            'courses' => $myCourses
        ]);
    }

    public function buy(string $id, CourseRepository $courseRepository, BillingClient $billingClient): Response
    {
        $course = $courseRepository->findOneBy(['id' => $id]);

        if (is_null($course)) {
            $this->createNotFoundException();
        }

        $response = $billingClient->buyCourse($course->getCode());
        if ($response['code'] == 200) {
            return $this->redirectToRoute('my_courses');
        }

        throw new HttpException($response['code'], $response['message']);
    }

    public function new(Request $request, BillingClient $billingClient): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        $error = "";

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $billingClient->createCourse($course);

                $em = $this->getDoctrine()->getManager();
                $em->persist($course);
                $em->flush();

                return $this->redirectToRoute('course_index', [], Response::HTTP_SEE_OTHER);
            } catch (\JsonException | \Exception $e) {
                $error = $e->getMessage();
            }
        }

        return $this->renderForm('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
            'error' => $error
        ]);
    }

    public function show(Course $course, BillingClient $billingClient): Response
    {
        $courses = $billingClient->getUserCourses(true);

        $isNotGranted =
            !isset($courses[$course->getCode()]) &&
            !$this->isGranted('ROLE_SUPER_ADMIN');

        if ($isNotGranted) {
            throw $this->createAccessDeniedException();
        }

        $lessonRep = $this->getDoctrine()->getRepository(Lesson::class);
        $lessons = $lessonRep->findBy(['course' => $course->getId()], ['serialNumber' => 'ASC']);

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'lessons' => $lessons
        ]);
    }

    public function edit(Request $request, Course $course, BillingClient $billingClient): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $billingClient->editCourse($course);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    public function delete(Request $request, Course $course, BillingClient $billingClient): Response
    {
        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            try {
                $billingClient->deleteCourse($course);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($course);
                $entityManager->flush();
            } catch (\Exception $e) {
                throw new HttpException(500, $e->getMessage());
            }
        }

        return $this->redirectToRoute('course_index', [], Response::HTTP_SEE_OTHER);
    }
}
