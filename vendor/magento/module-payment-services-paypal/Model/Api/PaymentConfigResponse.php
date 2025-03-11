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

namespace Magento\PaymentServicesPaypal\Model\Api;

use Magento\Framework\DataObject;
use Magento\PaymentServicesPaypal\Api\PaymentConfigResponseInterface;

class PaymentConfigResponse extends DataObject implements PaymentConfigResponseInterface
{
    /**
     * @inheritdoc
     */
    public function getApplePay()
    {
        return $this->getData(self::DATA_APPLE_PAY);
    }

    /**
     * @inheritdoc
     */
    public function setApplePay($applePay)
    {
        return $this->setData(self::DATA_APPLE_PAY, $applePay);
    }

    /**
     * @inheritdoc
     */
    public function getGooglePay()
    {
        return $this->getData(self::DATA_GOOGLE_PAY);
    }

    /**
     * @inheritdoc
     */
    public function setGooglePay($googlePay)
    {
        return $this->setData(self::DATA_GOOGLE_PAY, $googlePay);
    }

    /**
     * @inheritdoc
     */
    public function getHostedFields()
    {
        return $this->getData(self::DATA_HOSTED_FIELDS);
    }

    /**
     * @inheritdoc
     */
    public function setHostedFields($hostedFields)
    {
        return $this->setData(self::DATA_HOSTED_FIELDS, $hostedFields);
    }

    /**
     * @inheritdoc
     */
    public function getSmartButtons()
    {
        return $this->getData(self::DATA_SMART_BUTTONS);
    }

    /**
     * @inheritdoc
     */
    public function setSmartButtons($smartButtons)
    {
        return $this->setData(self::DATA_SMART_BUTTONS, $smartButtons);
    }
}
