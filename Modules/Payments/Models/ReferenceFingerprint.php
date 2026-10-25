<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Platform-level index of transfer references already used, holding only
 * a fingerprint and the first Payment that used it.
 */
#[Fillable(['fingerprint', 'first_payment_id'])]
class ReferenceFingerprint extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'payment_reference_fingerprints';
}
