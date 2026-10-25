<?php

namespace Modules\Payments\Support;

use finfo;
use GdImage;
use Illuminate\Http\UploadedFile;
use Modules\Payments\Exceptions\ReceiptCannotBeSubmitted;

/**
 * Decides what an uploaded file really is from its bytes, never its name or
 * declared type. Images are decoded and re-encoded so metadata and embedded
 * payloads are dropped; PDFs must carry the PDF signature.
 */
class ReceiptInspector
{
    /**
     * @throws ReceiptCannotBeSubmitted
     */
    public function inspect(UploadedFile $file): ReceiptFile
    {
        $bytes = $file->isValid() ? (string) file_get_contents($file->getRealPath()) : '';

        if ($bytes === '') {
            throw new ReceiptCannotBeSubmitted('receipt', __('payments::receipt.errors.unreadable'));
        }

        if (strlen($bytes) > config('payments.max_receipt_kilobytes') * 1024) {
            throw new ReceiptCannotBeSubmitted('receipt', __('payments::receipt.errors.too_large'));
        }

        return match ((new finfo(FILEINFO_MIME_TYPE))->buffer($bytes)) {
            'image/jpeg' => $this->image($bytes, 'image/jpeg', 'jpg'),
            'image/png' => $this->image($bytes, 'image/png', 'png'),
            'image/webp' => $this->image($bytes, 'image/webp', 'webp'),
            'application/pdf' => $this->pdf($bytes),
            default => throw new ReceiptCannotBeSubmitted('receipt', __('payments::receipt.errors.type')),
        };
    }

    private function pdf(string $bytes): ReceiptFile
    {
        if (! str_starts_with($bytes, '%PDF-')) {
            throw new ReceiptCannotBeSubmitted('receipt', __('payments::receipt.errors.type'));
        }

        return new ReceiptFile($bytes, 'application/pdf', 'pdf');
    }

    private function image(string $bytes, string $mime, string $extension): ReceiptFile
    {
        $image = @imagecreatefromstring($bytes);

        if (! $image instanceof GdImage) {
            throw new ReceiptCannotBeSubmitted('receipt', __('payments::receipt.errors.type'));
        }

        ob_start();

        match ($mime) {
            'image/png' => $this->preserveAlpha($image) && imagepng($image),
            'image/webp' => $this->preserveAlpha($image) && imagewebp($image, null, 90),
            default => imagejpeg($image, null, 90),
        };

        return new ReceiptFile((string) ob_get_clean(), $mime, $extension);
    }

    private function preserveAlpha(GdImage $image): bool
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return true;
    }
}
