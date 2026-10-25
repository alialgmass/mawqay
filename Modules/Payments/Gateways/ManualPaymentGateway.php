<?php

namespace Modules\Payments\Gateways;

use Illuminate\Http\UploadedFile;
use Modules\Payments\Actions\SubmitReceipt;
use Modules\Payments\Contracts\PaymentGateway;
use Modules\Payments\Models\Payment;
use Modules\Sales\Models\Order;

/**
 * Records the Customer's Receipt and leaves the decision to a Platform Admin.
 */
class ManualPaymentGateway implements PaymentGateway
{
    public function __construct(private readonly SubmitReceipt $submit) {}

    public function submit(Order $order, UploadedFile $receipt, ?string $transferReference = null, ?string $senderPhone = null): Payment
    {
        return ($this->submit)($order, $receipt, $transferReference, $senderPhone);
    }
}
