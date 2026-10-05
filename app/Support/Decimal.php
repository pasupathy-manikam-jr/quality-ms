<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Exact comparison and display of measured values and limits, which are stored as
 * DECIMAL and arrive as strings. Never compare them as floats (0.1 + 0.2 != 0.3).
 */
class Decimal
{
    private const SCALE = 6;

    /**
     * @return int -1, 0 or 1
     */
    public static function compare(string $a, string $b): int
    {
        return bccomp(self::normalize($a), self::normalize($b), self::SCALE);
    }

    /**
     * "0.240000" -> "0.24", "355.000000" -> "355".
     */
    public static function format(string $value): string
    {
        $value = self::normalize($value);

        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }

    /**
     * Accepts what the "numeric" + "decimal" validation rules let through ("+5", ".5", "5.")
     * and returns the plain form bcmath expects ("5", "0.5", "5").
     *
     * @return numeric-string
     */
    private static function normalize(string $value): string
    {
        if (! preg_match('/^([+-]?)(\d*)(?:\.(\d*))?$/', trim($value), $m) || ($m[2] === '' && ($m[3] ?? '') === '')) {
            throw new InvalidArgumentException("[{$value}] is not a plain decimal number.");
        }

        $sign = $m[1] === '-' ? '-' : '';
        $whole = $m[2] === '' ? '0' : $m[2];
        $fraction = $m[3] ?? '';

        $normalized = $sign.$whole.($fraction === '' ? '' : '.'.$fraction);

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException("[{$value}] is not a plain decimal number.");
        }

        return $normalized;
    }
}
