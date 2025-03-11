<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Data\Vault;

use PayPal\Braintree\Api\Data\PaymentInterface;
use PayPal\Braintree\Gateway\Data\PaymentAdapterInterface;

class PaymentAdapter implements PaymentAdapterInterface
{
    /**
     * @var PaymentInterface
     */
    private PaymentInterface $payment;

    /**
     * @param PaymentInterface $payment
     */
    public function __construct(PaymentInterface $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Get the braintree payment method code.
     *
     * @return string
     */
    public function getPaymentMethodCode(): string
    {
        return $this->payment->getPaymentMethodCode() ?? '';
    }

    /**
     * Get the payment method nonce.
     *
     * @return string
     */
    public function getPaymentMethodNonce(): string
    {
        return $this->payment->getPaymentMethodNonce() ?? '';
    }

    /**
     * Get the device data.
     *
     * @return string|null
     */
    public function getDeviceData(): ?string
    {
        return $this->payment->getDeviceData();
    }
}
