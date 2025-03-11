<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Adapter\PaymentMethod;

use Braintree\CreditCard;
use Braintree\PayPalAccount;

interface PaymentTokenAdapterFactoryInterface
{
    /**
     * Create payment token adapter
     *
     * @param string $paymentMethodCode
     * @param CreditCard|PayPalAccount $paymentMethod
     * @return PaymentTokenAdapterInterface
     */
    public function create(
        string $paymentMethodCode,
        CreditCard|PayPalAccount $paymentMethod
    ): PaymentTokenAdapterInterface;
}
