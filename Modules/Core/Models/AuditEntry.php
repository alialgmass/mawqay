<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * One accountable decision: who did what to which record, with before and after values.
 * Append-only: it can be written and read, never changed or removed.
 */
#[Fillable(['store_id', 'actor_id', 'actor_role', 'action', 'subject_type', 'subject_id', 'before', 'after', 'reason', 'ip_address'])]
class AuditEntry extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit entries are append-only.'));
        static::deleting(fn () => throw new LogicException('Audit entries are append-only.'));
    }

    /**
     * Closes the bulk routes around the model guard.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return Builder<static>
     */
    public function newEloquentBuilder($query): Builder
    {
        return new class($query) extends Builder
        {
            public function update(array $values): never
            {
                throw new LogicException('Audit entries are append-only.');
            }

            public function delete(): never
            {
                throw new LogicException('Audit entries are append-only.');
            }

            public function forceDelete(): never
            {
                throw new LogicException('Audit entries are append-only.');
            }
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array'];
    }
}
