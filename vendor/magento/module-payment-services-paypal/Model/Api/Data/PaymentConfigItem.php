<?php
/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
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
 **************************************************************************/
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Api\Data;

use Magento\Framework\DataObject;
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigItemInterface;

/**
 * Class Config Data
 *
 */
class PaymentConfigItem extends DataObject implements PaymentConfigItemInterface
{
    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return $this->getData(self::CODE);
    }
    /**
     * @inheritdoc
     */
    public function setCode($code)
    {
        return $this->setData(self::CODE, $code);
    }
    /**
     * @inheritdoc
     */
    public function getSdkParams()
    {
        return $this->getData(self::SDK_PARAMS);
    }
    /**
     * @inheritdoc
     */
    public function setSdkParams($sdkParams)
    {
        return $this->setData(self::SDK_PARAMS, $sdkParams);
    }
    /**
     * @inheritdoc
     */
    public function hasIsVisible()
    {
        return $this->getData(self::IS_VISIBLE);
    }
    /**
     * @inheritdoc
     */
    public function setHasIsVisible($visible)
    {
        return $this->setData(self::IS_VISIBLE, $visible);
    }
    /**
     * @inheritdoc
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }
    /**
     * @inheritdoc
     */
    public function setSortOrder($sortOrder)
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * @inheritdoc
     */
    public function getPaymentIntent()
    {
        return $this->getData(self::PAYMENT_INTENT);
    }
    /**
     * @inheritdoc
     */
    public function setPaymentIntent($paymentIntent)
    {
        return $this->setData(self::PAYMENT_INTENT, $paymentIntent);
    }
    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->getData(self::TITLE);
    }
    /**
     * @inheritdoc
     */
    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }
}
