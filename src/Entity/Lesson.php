<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ORM\Entity(repositoryClass=LessonRepository::class)
 */
class Lesson
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Course::class)
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private Course $course;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $title;

    /**
     * @ORM\Column(type="text")
     */
    private string $content;

    // TODO: rename to $serialNumber
    /**
     * @ORM\Column(type="integer", name="serial_number")
     */
    private int $serialNumber;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getSerialNumber(): ?int
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(int $serialNumber): self
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint(
            'serialNumber',
            new LessThanOrEqual(['value' => 10000, 'message' => 'The serial number must be less than 10000'])
        );
        $metadata->addPropertyConstraint('course', new Assert\NotNull(['message' => 'course must be not null']));
    }
}
