<?php

namespace Modules\Ledger\Enums;

enum LedgerAccount: string
{
    case PlatformCash = 'platform_cash';
    case VendorPayablePending = 'vendor_payable_pending';
    case VendorPayableAvailable = 'vendor_payable_available';
    case VendorPayoutPending = 'vendor_payout_pending';
    case PlatformCommissionRevenue = 'platform_commission_revenue';

    /**
     * Accounts holding money owed to a Vendor.
     *
     * @return list<self>
     */
    public static function vendorPayables(): array
    {
        return [self::VendorPayablePending, self::VendorPayableAvailable, self::VendorPayoutPending];
    }

    /**
     * Cash is an asset (debits add); every other account is a liability or revenue (credits add).
     */
    public function isDebitNormal(): bool
    {
        return $this === self::PlatformCash;
    }
}
