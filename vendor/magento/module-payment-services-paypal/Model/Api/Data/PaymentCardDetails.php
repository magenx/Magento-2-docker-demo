<?php

/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2023 Adobe
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
 */

declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Api\Data;

use Magento\Framework\DataObject;
use Magento\PaymentServicesPaypal\Api\Data\PaymentCardDetailsInterface;

/**
 * Class Payment Address
 */
class PaymentCardDetails extends DataObject implements PaymentCardDetailsInterface
{
    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
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
    public function setLastDigits($lastDigits)
    {
        return $this->setData(self::LAST_DIGITS, $lastDigits);
    }

    /**
     * @inheritDoc
     */
    public function getCardExpiryMonth()
    {
        return $this->getData(self::CARD_EXPIRY_MONTH);
    }

    /**
     * @inheritDoc
     */
    public function setCardExpiryMonth($cardExpiryMonth)
    {
        return $this->setData(self::CARD_EXPIRY_MONTH, $cardExpiryMonth);
    }

    /**
     * @inheritDoc
     */
    public function getCardExpiryYear()
    {
        return $this->getData(self::CARD_EXPIRY_YEAR);
    }

    /**
     * @inheritDoc
     */
    public function setCardExpiryYear($cardExpiryYear)
    {
        return $this->setData(self::CARD_EXPIRY_YEAR, $cardExpiryYear);
    }

    /**
     * @inheritDoc
     */
    public function getBinDetails()
    {
        return $this->getData(self::BIN_DETAILS);
    }

    /**
     * @inheritDoc
     */
    public function setBinDetails($binDetails)
    {
        return $this->setData(self::BIN_DETAILS, $binDetails);
    }
}
