<?php

namespace Modules\Sales\Enums;

enum RefundReason: string
{
    case WrongSize = 'wrong_size';
    case ChangedMind = 'changed_mind';
    case Defective = 'defective';
    case NotDelivered = 'not_delivered';
    case Other = 'other';

    public function label(): string
    {
        return __('sales::orders.refund.reasons.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $reason): array => [$reason->value => $reason->label()])->all();
    }
}
