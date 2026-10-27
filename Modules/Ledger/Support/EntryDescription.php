<?php

namespace Modules\Ledger\Support;

use Modules\Ledger\Models\LedgerEntry;

/**
 * A plain-language line for an entry, in the current locale.
 */
final class EntryDescription
{
    public static function for(LedgerEntry $entry): string
    {
        $type = $entry->meta['type'] ?? 'other';

        if (str_starts_with($type, 'payout_') && in_array($step = substr($type, 7), ['request', 'paid', 'cancel', 'reject'], true)) {
            return __("ledger::payouts.description.{$step}.{$entry->direction->value}");
        }

        $type = in_array($type, ['payment', 'release', 'reversal'], true) ? $type : 'other';

        return __("ledger::ledger.description.{$type}.{$entry->direction->value}", ['number' => $entry->meta['order_number'] ?? '']);
    }
}
