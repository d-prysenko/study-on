<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('date_russian_format', [$this, 'calculateArea']),
        ];
    }
//1 год
//2 года
//3 года
//4 года
//5 лет
//6 лет
//7 лет
//8 лет
//9 лет
//10 лет
//11 лет
//12 лет
//13 лет
//14 лет
//15 лет
//..
//20 лет
//21 год
//..
//24 года
//25 лет
//..
//30 лет
//31 год
    public function calculateArea(string $date)
    {
        $interval = new \DateInterval($date);
        $res = "";

        if ($interval->y !== 0) {
            $year = $interval->y;
            $res .= $year;
            if (($year >= 5 && $year <= 20) || $year % 10 >= 5) {
                $res .= " лет";
            } elseif ($year % 10 === 1) {
                $res .= " год";
            } elseif ($year % 10 >= 2 && $year % 10 <= 4) {
                $res .= " года";
            }
        }
//    1 месяц
//    2 месяца
//    3 месяца
//    4 месяца
//    5 месяцев
//    6 месяцев
//    7 месяцев
//    8 месяцев
//    9 месяцев
//    10 месяцев
//    11 месяцев
        if ($interval->m !== 0) {
            $month = $interval->m;
            $res .= " " . $month . " месяц";
            if ($month >= 2 && $month <= 4) {
                $res .= "а";
            } elseif ($month >= 5) {
                $res .= "ев";
            }
        }

//    1 день
//    2 дня
//    3 дня
//    4 дня
//    5 дней
//    6 дней
//    7 дней
//    8 дней
//    9 дней
//    10 дней
//    11 дней
//    12 дней
//    13 дней
//    14 дней
//    15 дней
//    16 дней
//    17 дней
//    18 дней
//    19 дней
//    20 дней
//    21 день
//    22 дня
//    23 дня
//    24 дня
//    25 дней

        if ($interval->d !== 0) {
            $day = $interval->d;
            $res .= " " . $day;
            if (($day >= 5 && $day <= 20) || $day % 10 >= 5) {
                $res .= " дней";
            } elseif ($day % 10 === 1) {
                $res .= " день";
            } elseif ($day % 10 >= 2 && $day % 10 <= 4) {
                $res .= " дня";
            }
        }

        return $res;
    }
}