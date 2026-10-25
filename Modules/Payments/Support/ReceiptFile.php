<?php

namespace Modules\Payments\Support;

/**
 * A Receipt that passed inspection: bytes safe to store, with the type they were found to be.
 */
final class ReceiptFile
{
    public function __construct(
        public readonly string $contents,
        public readonly string $mime,
        public readonly string $extension,
    ) {}
}
