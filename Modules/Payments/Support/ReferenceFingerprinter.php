<?php

namespace Modules\Payments\Support;

/**
 * Makes two spellings of the same transfer reference compare equal.
 */
final class ReferenceFingerprinter
{
    public static function normalize(string $reference): string
    {
        return mb_strtolower((string) preg_replace('/[^\p{L}\p{N}]+/u', '', $reference));
    }

    public static function fingerprint(string $reference): ?string
    {
        $normalized = self::normalize($reference);

        return $normalized === '' ? null : hash('sha256', $normalized);
    }
}
