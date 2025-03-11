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
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSdkParamsInterface;

/**
 * Class Config Data
 */
class PaymentConfigSdkParams extends DataObject implements PaymentConfigSdkParamsInterface
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->getData(self::PARAM_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        return $this->setData(self::PARAM_NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getValue()
    {
        return $this->getData(self::PARAM_VALUE);
    }

    /**
     * @inheritdoc
     */
    public function setValue($value)
    {
        return $this->setData(self::PARAM_VALUE, $value);
    }
}
