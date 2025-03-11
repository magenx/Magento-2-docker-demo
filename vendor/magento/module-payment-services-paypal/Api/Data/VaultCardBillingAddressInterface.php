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

namespace Magento\PaymentServicesPaypal\Api\Data;

interface VaultCardBillingAddressInterface
{
    public const ADDRESS_LINE_1 = 'address_line_1';
    public const ADDRESS_LINE_2 = 'address_line_2';
    public const CITY = 'city';
    public const REGION = 'region';
    public const POSTAL_CODE = 'postal_code';
    public const COUNTRY_CODE = 'country_code';

    /**
     * Get line 1 of address
     *
     * @return string
     */
    public function getAddressLine1();

    /**
     * Set line 1 of address
     *
     * @param string $street
     * @return $this
     */
    public function setAddressLine1(string $street);

    /**
     * Get line 2 of address
     *
     * @return string
     */
    public function getAddressLine2();

    /**
     * Set line 2 of address
     *
     * @param string $street
     * @return $this
     */
    public function setAddressLine2(string $street);

    /**
     * Get city
     *
     * @return string
     */
    public function getCity();

    /**
     * Set city
     *
     * @param string $city
     * @return $this
     */
    public function setCity(string $city);

    /**
     * Get region
     *
     * @return string
     */
    public function getRegion();

    /**
     * Set region
     *
     * @param string $region
     * @return $this
     */
    public function setRegion(string $region);

    /**
     * Get postal code
     *
     * @return string
     */
    public function getPostalCode();

    /**
     * Set postal code
     *
     * @param string $postalCode
     * @return $this
     */
    public function setPostalCode(string $postalCode);

    /**
     * Get country code
     *
     * @return string
     */
    public function getCountryCode();

    /**
     * Set country code
     *
     * @param string $countryCode
     * @return $this
     */
    public function setCountryCode(string $countryCode);
}
