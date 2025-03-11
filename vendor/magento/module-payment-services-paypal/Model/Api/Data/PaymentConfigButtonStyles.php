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
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigButtonStylesInterface;

/**
 * Class Config Data
 */
class PaymentConfigButtonStyles extends DataObject implements PaymentConfigButtonStylesInterface
{

    /**
     * @inheritdoc
     */
    public function getLayout()
    {
        return $this->getData(self::LAYOUT);
    }

    /**
     * @inheritdoc
     */
    public function setLayout($layout)
    {
        return $this->setData(self::LAYOUT, $layout);
    }
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
    public function getShape()
    {
        return $this->getData(self::SHAPE);
    }

    /**
     * @inheritdoc
     */
    public function setShape($shape)
    {
        return $this->setData(self::SHAPE, $shape);
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->getData(self::LABEL);
    }

    /**
     * @inheritdoc
     */
    public function setLabel($label)
    {
        return $this->setData(self::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function hasTagline()
    {
        return $this->getData(self::TAGLINE);
    }

    /**
     * @inheritdoc
     */
    public function setHasTagline($showTagline)
    {
        return $this->setData(self::TAGLINE, $showTagline);
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
    public function getUseDefaultHeight()
    {
        return $this->getData(self::DEFAULT_HEIGHT);
    }

    /**
     * @inheritdoc
     */
    public function setUseDefaultHeight($useDefaultHeight)
    {
        return $this->setData(self::DEFAULT_HEIGHT, $useDefaultHeight);
    }
}
