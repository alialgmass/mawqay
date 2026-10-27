<?php

namespace Modules\Ledger\Enums;

enum PayoutMethod: string
{
    case VodafoneCash = 'vodafone_cash';
    case InstaPay = 'instapay';
    case Bank = 'bank';

    public function label(): string
    {
        return __('ledger::payouts.methods.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $method): array => [$method->value => $method->label()])->all();
    }
}
