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

interface VaultCardDetailsInterface
{
    public const BRAND = 'brand';
    public const TYPE = 'type';
    public const LAST_DIGITS = 'last_digits';
    public const EXPIRY = 'expiry';
    public const BILLING_ADDRESS = 'billing_address';
    public const CARDHOLDER_NAME = 'cardholder_name';

    /**
     * Get brand
     *
     * @return string
     */
    public function getBrand();

    /**
     * Set brand
     *
     * @param string $brand
     * @return $this
     */
    public function setBrand(string $brand);

    /**
     * Get type
     *
     * @return string
     */
    public function getType();

    /**
     * Set type
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type);

    /**
     * Get last digits
     *
     * @return string
     */
    public function getLastDigits();

    /**
     * Set last digits
     *
     * @param string $lastDigits
     * @return $this
     */
    public function setLastDigits(string $lastDigits);

    /**
     * Get card expiry
     *
     * @return string
     */
    public function getExpiry();

    /**
     * Set card expiry
     *
     * @param string $expiry
     * @return $this
     */
    public function setExpiry(string $expiry);

    /**
     * Get billing address
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\VaultCardBillingAddressInterface
     */
    public function getBillingAddress();

    /**
     * Set billing address
     *
     * @param \Magento\PaymentServicesPaypal\Api\Data\VaultCardBillingAddressInterface $address
     * @return $this
     */
    public function setBillingAddress(VaultCardBillingAddressInterface $address);

    /**
     * Get cardholder name
     *
     * @return string
     */
    public function getCardholderName();

    /**
     * Set cardholder name
     *
     * @param string $cardholderName
     * @return $this
     */
    public function setCardholderName(string $cardholderName);
}
