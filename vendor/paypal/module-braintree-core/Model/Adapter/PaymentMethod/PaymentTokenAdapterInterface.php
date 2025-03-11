<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Adapter\PaymentMethod;

interface PaymentTokenAdapterInterface
{
    /**
     * Get the payment method code.
     *
     * @return string
     */
    public function getPaymentMethodCode(): string;

    /**
     * Get Payment token type.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Get Gateway token.
     *
     * @return string
     */
    public function getGatewayToken(): string;

    /**
     * Get the token expiration date.
     *
     * @return string
     */
    public function getExpiresAt(): string;

    /**
     * Get the token details.
     *
     * @return string
     */
    public function getTokenDetails(): string;
}
