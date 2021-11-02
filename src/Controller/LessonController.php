<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Form\LessonType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LessonController extends AbstractController
{
    public function new(Request $request): Response
    {
        $lesson = new Lesson();
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($lesson);
            $entityManager->flush();

            return $this->redirectToRoute(
                'course_show',
                ['id' => $lesson->getCourse()->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        if (!$form->isSubmitted()) {
            $course_id = $request->query->get('course');

            $courseRep = $this->getDoctrine()->getRepository(Course::class);
            $course = $courseRep->findOneBy(['id' => $course_id]);

            if (!$course) {
                throw new NotFoundHttpException('There is no course with such id');
            }

            $form->get('course')->setData($course);
        }

        return $this->renderForm('lesson/new.html.twig', [
            'form' => $form,
        ]);
    }

    public function show(Lesson $lesson): Response
    {
        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
        ]);
    }

    public function edit(Request $request, Lesson $lesson): Response
    {
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'course_show',
                ['id' => $lesson->getCourse()->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    public function delete(Request $request, Lesson $lesson): Response
    {
        if ($this->isCsrfTokenValid('delete' . $lesson->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($lesson);
            $entityManager->flush();
        }

        return $this->redirectToRoute(
            'course_show',
            ['id' => $lesson->getCourse()->getId()],
            Response::HTTP_SEE_OTHER
        );
    }
}
