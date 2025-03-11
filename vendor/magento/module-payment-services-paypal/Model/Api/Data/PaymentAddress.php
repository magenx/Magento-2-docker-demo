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
use Magento\PaymentServicesPaypal\Api\Data\PaymentAddressInterface;
use Magento\PaymentServicesPaypal\Api\Data\PaymentOrderInterface;

class PaymentAddress extends DataObject implements PaymentAddressInterface
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
    public function setName(string $name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function getAddressLine1()
    {
        return $this->getData(self::ADDRESS_LINE_1);
    }

    /**
     * @inheritDoc
     */
    public function setAddressLine1(string $addressLine1)
    {
        return $this->setData(self::ADDRESS_LINE_1, $addressLine1);
    }

    /**
     * @inheritDoc
     */
    public function getAddressLine2()
    {
        return $this->getData(self::ADDRESS_LINE_2);
    }

    /**
     * @inheritDoc
     */
    public function setAddressLine2(string $addressLine2)
    {
        return $this->setData(self::ADDRESS_LINE_2, $addressLine2);
    }

    /**
     * @inheritDoc
     */
    public function getAdminArea1()
    {
        return $this->getData(self::ADMIN_AREA_1);
    }

    /**
     * @inheritDoc
     */
    public function setAdminArea1(string $adminArea1)
    {
        return $this->setData(self::ADMIN_AREA_1, $adminArea1);
    }

    /**
     * @inheritDoc
     */
    public function getAdminArea2()
    {
        return $this->getData(self::ADMIN_AREA_2);
    }

    /**
     * @inheritDoc
     */
    public function setAdminArea2(string $adminArea2)
    {
        return $this->setData(self::ADMIN_AREA_2, $adminArea2);
    }

    /**
     * @inheritDoc
     */
    public function getPostalCode()
    {
        return $this->getData(self::POSTAL_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setPostalCode(string $postalCode)
    {
        return $this->setData(self::POSTAL_CODE, $postalCode);
    }

    /**
     * @inheritDoc
     */
    public function getCountryCode()
    {
        return $this->getData(self::COUNTRY_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setCountryCode(string $countryCode)
    {
        return $this->setData(self::COUNTRY_CODE, $countryCode);
    }
}
