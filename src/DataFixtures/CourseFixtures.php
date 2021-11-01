<?php

namespace App\DataFixtures;

use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Course;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $course = new Course();
        $course->setName("Базы данных");
        $course->setCode("db");
        $course->setDescription(
            "Этот курс - проводник в мир, в котором работа с базами данных не является чем-то сложным и непонятным."
        );
        $manager->persist($course);

        $lesson1 = new Lesson();
        $lesson1->setTitle("Предпосылки появления баз данных");
        $lesson1->setContent("Ну короче записывать в файл неудобно, поэтому придумали базы данных. Конец.");
        $lesson1->setSerialNumber(1);
        $lesson1->setCourse($course);
        $manager->persist($lesson1);

        $lesson1 = new Lesson();
        $lesson1->setTitle("Виды баз данных");
        $lesson1->setContent("Ну короче есть реляционные, а есть нереляционные базы данных. Конец.");
        $lesson1->setSerialNumber(2);
        $lesson1->setCourse($course);
        $manager->persist($lesson1);

        $course = new Course();
        $course->setName("Алгебра");
        $course->setCode("math");
        $course->setDescription(
            "Хочешь разобраться в математических основах работы игрового движка 
            или просто хочешь подготовиться к экзамену? Тогда этот курс для тебя!"
        );
        $manager->persist($course);

        $lesson1 = new Lesson();
        $lesson1->setTitle("Линейная алгебра и аналитическая геометрия");
        $lesson1->setContent(
            "Ну короче есть линейная алгебра, а есть аналитическая геометрия.
             Первое - это про матрицы, определители, линейные пространства и операторы. 
             Второе - про прямые, кривые, поверхности и т.п."
        );
        $lesson1->setSerialNumber(1);
        $lesson1->setCourse($course);
        $manager->persist($lesson1);

        $manager->flush();
    }
}
