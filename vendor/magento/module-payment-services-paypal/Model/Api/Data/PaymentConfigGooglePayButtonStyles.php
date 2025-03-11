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
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigGooglePayButtonStylesInterface;

/**
 * Class Config Data
 */
class PaymentConfigGooglePayButtonStyles extends DataObject implements PaymentConfigGooglePayButtonStylesInterface
{
    /**
     * @inheritdoc
     */
    public function getColor()
    {
        return $this->getData(self::COLOR);
    }

    /**
     * @inheritdoc
     */
    public function setColor($color)
    {
        return $this->setData(self::COLOR, $color);
    }

    /**
     * @inheritdoc
     */
    public function getHeight()
    {
        return $this->getData(self::HEIGHT);
    }

    /**
     * @inheritdoc
     */
    public function setHeight($height)
    {
        return $this->setData(self::HEIGHT, $height);
    }

    /**
     * @inheritdoc
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }
}
