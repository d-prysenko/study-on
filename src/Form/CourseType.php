<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', null, [
                'label' => 'Код'
            ])
            ->add('name', null, [
                'label' => 'Название'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание'
            ])
            ->add('type', ChoiceType::class, [
                'choices'  => [
                    'Платный' => 'buy',
                    'Бесплатный' => 'free',
                    'Аренда' => 'rent',
                ],
                'label' => 'Тип'
            ])
            ->add('price', null, [
                'label' => 'Стоимость'
            ])
            ->add('duration', DateIntervalType::class, [
                'label' => 'Длительность аренды',
                'labels' => [
                    'years' => "Год",
                    'months' => "Месяц",
                    'days' => "День",
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
