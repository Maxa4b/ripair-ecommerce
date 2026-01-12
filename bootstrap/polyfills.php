<?php

if (!function_exists('mb_split')) {
    /**
     * Minimal polyfill for mb_split for environments without ext-mbstring.
     */
    function mb_split(string $pattern, string $string, int $limit = -1): array
    {
        $delimiter = '/';
        $regex = $delimiter . $pattern . $delimiter . 'u';

        return preg_split($regex, $string, $limit === -1 ? 0 : $limit);
    }
}
