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

use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSmartButtonsInterface;

/**
 * Class Config Data
 */
class PaymentConfigSmartButtons extends PaymentConfigItem implements PaymentConfigSmartButtonsInterface
{
    /**
     * @inheritdoc
     */
    public function hasDisplayMessage()
    {
        return $this->getData(self::DISPLAY_MESSAGE);
    }
    /**
     * @inheritdoc
     */
    public function setHasDisplayMessage($canDisplayMessage)
    {
        return $this->setData(self::DISPLAY_MESSAGE, $canDisplayMessage);
    }
    /**
     * @inheritdoc
     */
    public function hasDisplayVenmo()
    {
        return $this->getData(self::DISPLAY_VENMO);
    }
    /**
     * @inheritdoc
     */
    public function setHasDisplayVenmo($canDisplayVenmo)
    {
        return $this->setData(self::DISPLAY_VENMO, $canDisplayVenmo);
    }
    /**
     * @inheritdoc
     */
    public function getMessageStyles()
    {
        return $this->getData(self::MESSAGE_STYLES);
    }
    /**
     * @inheritdoc
     */
    public function setMessageStyles($messageStyles)
    {
        return $this->setData(self::MESSAGE_STYLES, $messageStyles);
    }
    /**
     * @inheritdoc
     */
    public function getButtonStyles()
    {
        return $this->getData(self::BUTTON_STYLES);
    }
    /**
     * @inheritdoc
     */
    public function setButtonStyles($buttonStyles)
    {
        return $this->setData(self::BUTTON_STYLES, $buttonStyles);
    }
}
