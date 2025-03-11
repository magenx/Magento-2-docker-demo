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

namespace Magento\PaymentServicesPaypal\Api\Data;

/**
 * Interface PaymentOrderInterface
 * @api
 */
interface PaymentOrderInterface
{
    public const DATA_ID = 'id';
    public const DATA_MP_ORDER_ID = 'mp_order_id';
    public const DATA_STATUS = 'status';
    public const DATA_AMOUNT = 'amount';
    public const DATA_CURRENCY_CODE = 'currency_code';

    /**
     * Get payment order id
     *
     * @return string
     */
    public function getId();

    /**
     * Set payment order id
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id);

    /**
     * Get payment order mpOrderId
     *
     * @return string
     */
    public function getMpOrderId();

    /**
     * Set payment order mpOrderId
     *
     * @param string $mpOrderId
     * @return $this
     */
    public function setMpOrderId(string $mpOrderId);

    /**
     * Get payment order status
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set payment order status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status);

    /**
     * Get payment order amount
     *
     * @return float
     */
    public function getAmount();

    /**
     * Set payment order amount
     *
     * @param float $amount
     * @return $this
     */
    public function setAmount(float $amount);

    /**
     * Get payment order currency code
     *
     * @return string
     */
    public function getCurrencyCode();

    /**
     * Set payment order currency code
     *
     * @param string $currencyCode
     * @return $this
     */
    public function setCurrencyCode(string $currencyCode);
}
