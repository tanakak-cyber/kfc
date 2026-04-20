<?php

namespace App\Support;

/**
 * 公開ページ（周知）向けの数値表示。小数点以下は最大1桁（末尾の不要な 0 は省略）。
 */
final class PublicDisplayNumber
{
    /**
     * 小数第1位まで（四捨五入）。整数に近い場合は小数なし表記。
     */
    public static function upToOneDecimal(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $x = round((float) $value, 1);
        $s = number_format($x, 1, '.', '');

        return rtrim(rtrim($s, '0'), '.') ?: '0';
    }

    public static function upToOneDecimalOrDash(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return self::upToOneDecimal($value);
    }
}
