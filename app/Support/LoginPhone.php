<?php

namespace App\Support;

class LoginPhone
{
    public static function normalize(string $value): string
    {
        $value = preg_replace('/[\s().-]+/', '', trim($value));
        if (str_starts_with($value, '+84')) {
            $value = '0'.substr($value, 3);
        }

        return $value;
    }
}
