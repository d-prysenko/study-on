<?php

namespace App\DTO;

use App\Entity\Course;

class CourseDTO
{
    public string $name;

    public string $code;

    public float $price;

    public static function fromCourse(Course $course): CourseDTO
    {
        $courseDTO = new self();

        $courseDTO->name = $course->getName();
        $courseDTO->code = $course->getCode();
        $courseDTO->price = $course->getPrice();

        return $courseDTO;
    }
}