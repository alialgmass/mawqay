<?php

namespace Modules\Core\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\AuditEntry;

/**
 * Writes the audit trail. Call it inside the same transaction as the change it records, so a change
 * that fails leaves no entry and an entry never exists for a change that did not happen.
 */
final class Audit
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public static function record(string $action, ?Model $subject = null, ?array $before = null, ?array $after = null, ?string $reason = null, ?int $storeId = null, ?int $actorId = null): AuditEntry
    {
        $user = Auth::guard('web')->user();
        $actor = $user instanceof User ? $user : null;

        return AuditEntry::query()->create([
            'store_id' => $storeId ?? ($subject?->getAttribute('store_id')),
            'actor_id' => $actorId ?? $actor?->getKey(),
            'actor_role' => $actor?->role?->value,
            'action' => $action,
            'subject_type' => $subject === null ? null : $subject->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
