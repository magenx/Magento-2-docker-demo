<?php

/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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
use Magento\PaymentServicesPaypal\Api\Data\VaultCardBillingAddressInterface;

class VaultCardBillingAddress extends DataObject implements VaultCardBillingAddressInterface
{
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
    public function setAddressLine1(string $street)
    {
        return $this->setData(self::ADDRESS_LINE_1, $street);
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
    public function setAddressLine2(string $street)
    {
        return $this->setData(self::ADDRESS_LINE_2, $street);
    }

    /**
     * @inheritDoc
     */
    public function getCity()
    {
        return $this->getData(self::CITY);
    }

    /**
     * @inheritDoc
     */
    public function setCity(string $city)
    {
        return $this->setData(self::CITY, $city);
    }

    /**
     * @inheritDoc
     */
    public function getRegion()
    {
        return $this->getData(self::REGION);
    }

    /**
     * @inheritDoc
     */
    public function setRegion(string $region)
    {
        return $this->setData(self::REGION, $region);
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
