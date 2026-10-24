<?php

namespace Modules\Auth\Support;

use Propaganistas\LaravelPhone\PhoneNumber as LibraryPhoneNumber;
use Throwable;

/**
 * Egyptian mobile numbers in one stored form (E.164), however they were typed.
 */
final class PhoneNumber
{
    public const REGION = 'EG';

    /**
     * The standard form of a number, or null when it is not a valid number.
     */
    public static function normalize(?string $input): ?string
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        try {
            $number = new LibraryPhoneNumber($input, self::REGION);

            return $number->isValid() ? $number->formatE164() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
