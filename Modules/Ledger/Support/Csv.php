<?php

namespace Modules\Ledger\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV downloads that open correctly in a spreadsheet: UTF-8 with a byte-order mark so Arabic survives,
 * decimal money, and text that cannot be read as a formula.
 */
final class Csv
{
    /**
     * @param  list<string>  $header
     * @param  iterable<list<string|int|float|null>>  $rows
     */
    public static function download(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map(self::cell(...), $header), escape: '\\');

            foreach ($rows as $row) {
                fputcsv($out, array_map(self::cell(...), $row), escape: '\\');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Piasters as decimal EGP, always two places.
     */
    public static function money(int $piasters): string
    {
        return number_format($piasters / 100, 2, '.', '');
    }

    /**
     * Text starting with a formula character is prefixed so a spreadsheet shows it instead of running it.
     * Numbers are left alone, so a negative amount stays a number.
     */
    public static function cell(string|int|float|null $value): string
    {
        $text = (string) $value;

        if ($text !== '' && ! is_numeric($text) && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$text;
        }

        return $text;
    }
}
