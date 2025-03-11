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

namespace Magento\PaymentServicesPaypal\Api\Data;

interface PaymentAddressInterface
{
    public const NAME = 'name';
    public const ADDRESS_LINE_1 = 'address_line_1';
    public const ADDRESS_LINE_2 = 'address_line_2';
    public const ADMIN_AREA_1 = 'admin_area_1';
    public const ADMIN_AREA_2 = 'admin_area_2';
    public const POSTAL_CODE = 'postal_code';
    public const COUNTRY_CODE = 'country_code';

    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * Set code
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name);

    /**
     * Get address line 1
     *
     * @return string
     */
    public function getAddressLine1();

    /**
     * Set address line 1
     *
     * @param string $addressLine1
     * @return $this
     */
    public function setAddressLine1(string $addressLine1);

    /**
     * Get address line 2
     *
     * @return string
     */
    public function getAddressLine2();

    /**
     * Set address line 1
     *
     * @param string $addressLine2
     * @return $this
     */
    public function setAddressLine2(string $addressLine2);

    /**
     * Get admin area 1
     *
     * @return string
     */
    public function getAdminArea1();

    /**
     * Set admin area 1
     *
     * @param string $adminArea1
     * @return $this
     */
    public function setAdminArea1(string $adminArea1);

    /**
     * Get admin area 2
     *
     * @return string
     */
    public function getAdminArea2();

    /**
     * Set admin area 2
     *
     * @param string $adminArea2
     * @return $this
     */
    public function setAdminArea2(string $adminArea2);

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
    public function setcountryCode(string $countryCode);
}
