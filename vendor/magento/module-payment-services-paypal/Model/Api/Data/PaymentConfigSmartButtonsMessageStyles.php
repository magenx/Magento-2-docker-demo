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
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSmartButtonsMessageStylesInterface;

/**
 * Class Config Data
 */
class PaymentConfigSmartButtonsMessageStyles extends DataObject implements
    PaymentConfigSmartButtonsMessageStylesInterface
{
    /**
     * @inheritdoc
     */
    public function getLogo()
    {
        return $this->getData(self::LOGO);
    }

    /**
     * @inheritdoc
     */
    public function setLogo($logo)
    {
        return $this->setData(self::LOGO, $logo);
    }

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
}
