<?php

namespace Modules\Tenancy\Enums;

enum StoreStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Suspended = 'suspended';

    /**
     * The statuses a Store may move to from this one: Draft opens to Active,
     * and Active and Suspended swap freely. Nothing returns to Draft.
     *
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::Active],
            self::Active => [self::Suspended],
            self::Suspended => [self::Active],
        };
    }

    public function canMoveTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }
}
