<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Api\Data;

interface PaymentInterface
{
    /**
     * Property Constants
     */
    public const PAYMENT_METHOD_CODE = 'payment_method_code';
    public const PAYMENT_METHOD_NONCE = 'payment_method_nonce';
    public const DEVICE_DATA = 'device_data';

    /**
     * Get the payment method code.
     *
     * @return string|null
     */
    public function getPaymentMethodCode(): ?string;

    /**
     * Set the payment method code.
     *
     * @param string $value
     * @return void
     */
    public function setPaymentMethodCode(string $value): void;

    /**
     * Get the payment method nonce.
     *
     * @return string|null
     */
    public function getPaymentMethodNonce(): ?string;

    /**
     * Set the payment method nonce.
     *
     * @param string $value
     * @return void
     */
    public function setPaymentMethodNonce(string $value): void;

    /**
     * Get the device data.
     *
     * @return string|null
     */
    public function getDeviceData(): ?string;

    /**
     * Set the device data.
     *
     * @param string $value
     * @return void
     */
    public function setDeviceData(string $value): void;
}
