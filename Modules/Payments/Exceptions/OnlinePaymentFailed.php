<?php

namespace Modules\Payments\Exceptions;

use RuntimeException;

/**
 * An online payment could not be started; the message is safe to show a Customer.
 */
class OnlinePaymentFailed extends RuntimeException {}
