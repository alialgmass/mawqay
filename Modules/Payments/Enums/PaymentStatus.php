<?php

namespace Modules\Payments\Enums;

enum PaymentStatus: string
{
    case AwaitingGateway = 'awaiting_gateway';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
