<?php

/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 **************************************************************************/

declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Api\Data;

use Magento\Framework\DataObject;
use Magento\PaymentServicesPaypal\Api\Data\VaultCardBillingAddressInterface;
use Magento\PaymentServicesPaypal\Api\Data\VaultCardDetailsInterface;

class VaultCardDetails extends DataObject implements VaultCardDetailsInterface, \JsonSerializable
{
    /**
     * @inheritDoc
     */
    public function getBrand()
    {
        return $this->getData(self::BRAND);
    }

    /**
     * @inheritDoc
     */
    public function setBrand(string $brand)
    {
        return $this->setData(self::BRAND, $brand);
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setType(string $type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritDoc
     */
    public function getLastDigits()
    {
        return $this->getData(self::LAST_DIGITS);
    }

    /**
     * @inheritDoc
     */
    public function setLastDigits(string $lastDigits)
    {
        return $this->setData(self::LAST_DIGITS, $lastDigits);
    }

    /**
     * @inheritDoc
     */
    public function getExpiry()
    {
        return $this->getData(self::EXPIRY);
    }

    /**
     * @inheritDoc
     */
    public function setExpiry(string $expiry)
    {
        return $this->setData(self::EXPIRY, $expiry);
    }

    /**
     * @inheritDoc
     */
    public function getBillingAddress()
    {
        return $this->getData(self::BILLING_ADDRESS);
    }

    /**
     * @inheritDoc
     */
    public function setBillingAddress(VaultCardBillingAddressInterface $address)
    {
        return $this->setData(self::BILLING_ADDRESS, $address);
    }

    /**
     * @inheritDoc
     */
    public function getCardholderName()
    {
        return $this->getData(self::CARDHOLDER_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setCardholderName(string $cardholderName)
    {
        return $this->setData(self::CARDHOLDER_NAME, $cardholderName);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
