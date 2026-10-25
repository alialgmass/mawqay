<?php

namespace Modules\Payments\Contracts;

use Illuminate\Http\UploadedFile;
use Modules\Payments\Models\Payment;
use Modules\Sales\Models\Order;

/**
 * How a Customer's payment for an Order is taken. The manual gateway records a
 * claim and awaits review; an online gateway would settle it itself.
 */
interface PaymentGateway
{
    public function submit(Order $order, UploadedFile $receipt, ?string $transferReference = null, ?string $senderPhone = null): Payment;
}
