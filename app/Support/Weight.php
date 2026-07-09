<?php

namespace App\Support;

final class Weight
{
    public const GRAMS_PER_POUND = 453.59237;
    public const GRAMS_PER_KG = 1000.0;

    public static function lbsToGrams(float $lbs): float
    {
        return round($lbs * self::GRAMS_PER_POUND, 2);
    }

    public static function gramsToLbs(float $grams): float
    {
        return round($grams / self::GRAMS_PER_POUND, 2);
    }

    public static function gramsToKg(float $grams): float
    {
        return $grams / self::GRAMS_PER_KG; // do NOT round here; let money calc round at the end
    }

    public static function lbsToKg(float $lbs): float
    {
        return $lbs * (self::GRAMS_PER_POUND / self::GRAMS_PER_KG); // ~0.45359237
    }
}