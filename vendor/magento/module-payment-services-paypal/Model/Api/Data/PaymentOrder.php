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
use Magento\PaymentServicesPaypal\Api\Data\PaymentOrderInterface;

/**
 * @api
 */
class PaymentOrder extends DataObject implements PaymentOrderInterface
{
    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->_getData(self::DATA_ID);
    }

    /**
     * @inheritDoc
     */
    public function getMpOrderId()
    {
        return $this->_getData(self::DATA_MP_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->_getData(self::DATA_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setId(string $id)
    {
        return $this->setData(self::DATA_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function setMpOrderId(string $mpOrderId)
    {
        return $this->setData(self::DATA_MP_ORDER_ID, $mpOrderId);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status)
    {
        return $this->setData(self::DATA_STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getAmount()
    {
        return $this->_getData(self::DATA_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setAmount(float $amount)
    {
        return $this->setData(self::DATA_AMOUNT, $amount);
    }

    /**
     * @inheritDoc
     */
    public function getCurrencyCode()
    {
        return $this->_getData(self::DATA_CURRENCY_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setCurrencyCode(string $currencyCode)
    {
        return $this->setData(self::DATA_CURRENCY_CODE, $currencyCode);
    }
}
