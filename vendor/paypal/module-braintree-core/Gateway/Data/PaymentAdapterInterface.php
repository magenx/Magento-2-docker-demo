<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Data;

interface PaymentAdapterInterface
{
    /**
     * Get the braintree payment method code.
     *
     * @return string
     */
    public function getPaymentMethodCode(): string;

    /**
     * Get the payment method nonce.
     *
     * @return string
     */
    public function getPaymentMethodNonce(): string;

    /**
     * Get the device data.
     *
     * @return string|null
     */
    public function getDeviceData(): ?string;
}
