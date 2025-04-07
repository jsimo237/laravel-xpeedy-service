<?php


use Carbon\Carbon;

if (!function_exists("date_is_expired")) {
    /**
     * Détermine si une date est expirée par rapport à une autre.
     *
     * @param string|DateTimeInterface $myDate
     * @param string|DateTimeInterface|null $compareTo
     * @param string $format
     * @return bool
     */
    function date_is_expired( $myDate, $compareTo = null, string $format = 'Y-m-d H:i:s'): bool
    {
        $myDate = Carbon::parse($myDate);
        $compareTo ??= Carbon::now();
        $compareDate = Carbon::parse($compareTo);

        return $myDate->format($format) < $compareDate->format($format);
    }
}
