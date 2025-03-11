<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model;

use Magento\Framework\DataObject;
use PayPal\Braintree\Api\Data\PaymentInterface;

class Payment extends DataObject implements PaymentInterface
{
    /**
     * Get the payment method code.
     *
     * @return string|null
     */
    public function getPaymentMethodCode(): ?string
    {
        return $this->_getData(self::PAYMENT_METHOD_CODE);
    }

    /**
     * Set the payment method code.
     *
     * @param string $value
     * @return void
     */
    public function setPaymentMethodCode(string $value): void
    {
        $this->setData(self::PAYMENT_METHOD_CODE, $value);
    }

    /**
     * Get the payment method nonce.
     *
     * @return string|null
     */
    public function getPaymentMethodNonce(): ?string
    {
        return $this->_getData(self::PAYMENT_METHOD_NONCE);
    }

    /**
     * Set the payment method nonce.
     *
     * @param string $value
     * @return void
     */
    public function setPaymentMethodNonce(string $value): void
    {
        $this->setData(self::PAYMENT_METHOD_NONCE, $value);
    }

    /**
     * Get the device data.
     *
     * @return string|null
     */
    public function getDeviceData(): ?string
    {
        return $this->_getData(self::DEVICE_DATA);
    }

    /**
     * Set the device data.
     *
     * @param string $value
     * @return void
     */
    public function setDeviceData(string $value): void
    {
        $this->setData(self::DEVICE_DATA, $value);
    }
}
