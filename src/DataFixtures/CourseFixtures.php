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
        $course = (new Course())
            ->setName("Базы данных")
            ->setCode("db")
            ->setDescription(
                "Этот курс - проводник в мир, в котором работа с базами данных не является чем-то сложным и непонятным."
            )
        ;

        $manager->persist($course);

        $lesson1 = (new Lesson())
            ->setTitle("Предпосылки появления баз данных")
            ->setContent("Ну короче записывать в файл неудобно, поэтому придумали базы данных. Конец.")
            ->setSerialNumber(1)
            ->setCourse($course)
        ;

        $manager->persist($lesson1);

        $lesson1 = (new Lesson())
            ->setTitle("Виды баз данных")
            ->setContent("Ну короче есть реляционные, а есть нереляционные базы данных. Конец.")
            ->setSerialNumber(2)
            ->setCourse($course)
        ;

        $manager->persist($lesson1);

        $course = (new Course())
            ->setName("Алгебра")
            ->setCode("math")
            ->setDescription(
                "Хочешь разобраться в математических основах работы игрового движка 
                или просто хочешь подготовиться к экзамену? Тогда этот курс для тебя!"
            )
        ;

        $manager->persist($course);

        $lesson1 = (new Lesson())
            ->setTitle("Линейная алгебра и аналитическая геометрия")
            ->setContent(
                "Ну короче есть линейная алгебра, а есть аналитическая геометрия.
                Первое - это про матрицы, определители, линейные пространства и операторы. 
                Второе - про прямые, кривые, поверхности и т.п."
            )
            ->setSerialNumber(1)
            ->setCourse($course)
        ;

        $manager->persist($lesson1);

        $manager->flush();
    }
}
