<?php

namespace App\Support;

use Filament\Support\RawJs;

/**
 * Single source of truth for how rupiah TextInputs format while typing.
 *
 * Alpine's $money() mask signature is (input, delimiter, thousands, precision) —
 * delimiter is the decimal-point character, thousands is the grouping separator,
 * precision is the decimal digit count. A previous call site passed 0 as the
 * third argument intending "0 decimals", but that argument is actually the
 * thousands separator: passing the integer 0 there gets string-coerced and
 * spliced into the digit stream as a literal "0" on every group boundary
 * (e.g. typing 3333 rendered as 30333). Precision must be passed as the
 * fourth argument instead.
 */
class MoneyInput
{
    public const THOUSANDS_SEPARATOR = '.';

    public static function mask(): RawJs
    {
        return RawJs::make("\$money(\$input, ',', '" . self::THOUSANDS_SEPARATOR . "', 0)");
    }
}
