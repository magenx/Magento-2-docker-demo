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

interface PaymentCardDetailsInterface
{
    public const NAME = 'name';
    public const LAST_DIGITS = 'last_digits';
    public const CARD_EXPIRY_MONTH = 'card_expiry_month';
    public const CARD_EXPIRY_YEAR = 'card_expiry_year';
    public const BIN_DETAILS = 'bin_details';

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
     * Get card expiry month
     *
     * @return string
     */
    public function getCardExpiryMonth();

    /**
     * Set card expiry month
     *
     * @param string $cardExpiryMonth
     * @return $this
     */
    public function setCardExpiryMonth(string $cardExpiryMonth);

    /**
     * Get card expiry year
     *
     * @return string
     */
    public function getCardExpiryYear();

    /**
     * Set card expiry year
     *
     * @param string $cardExpiryYear
     * @return $this
     */
    public function setCardExpiryYear(string $cardExpiryYear);

    /**
     * Get bin details
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentCardBinDetailsInterface
     */
    public function getBinDetails();

    /**
     * Set admin area 1
     *
     * @param \Magento\PaymentServicesPaypal\Api\Data\PaymentCardBinDetailsInterface $binDetails
     * @return $this
     */
    public function setBinDetails($binDetails);
}
