<?php


namespace App\Package\PrettyIds;


class BaseChange
{
    /**
     * @param string $number
     * @param int $fromBase
     * @param int $toBase
     * @return string
     */
    public static function convert(string $number, int $fromBase = 10, int $toBase = 36): string
    {
        $number = trim($number);
        if ($fromBase !== 10) {
            $len = strlen($number);
            $q   = 0;
            for ($i = 0; $i < $len; $i++) {
                $r = base_convert($number[$i], $fromBase, 10);
                $q = bcadd(bcmul($q, $fromBase), $r);
            }
        } else {
            $q = $number;
        }

        if ($toBase !== 10) {
            $s = '';
            while (bccomp($q, '0', 0) > 0) {
                $r = intval(bcmod($q, $toBase));
                $s = base_convert($r, 10, $toBase) . $s;
                $q = bcdiv($q, $toBase, 0);
            }
        } else {
            $s = $q;
        }

        return $s;
    }

    /**
     * We may need to know the max chars that are needed to represent a base number as another base
     *
     * @param int $fromChars
     * @param int $fromBase
     * @param int $toBase
     * @return int
     */
    public static function maxCharsForBaseDigits(int $fromChars, int $fromBase, int $toBase): int
    {
        $maxCharForBase = self::maxBaseCharacter($fromBase);
        $maxNum = str_pad("", $fromChars, $maxCharForBase);
        $maxConverted = self::convert($maxNum, $fromBase, $toBase);
        return strlen($maxConverted);
    }

    private static function maxBaseCharacter(int $base): string
    {
        return self::convert($base-1, 10, $base);
    }
}