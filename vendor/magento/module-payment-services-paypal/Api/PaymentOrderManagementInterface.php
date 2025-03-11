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

namespace Magento\PaymentServicesPaypal\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PaymentServicesPaypal\Api\Data\PaymentOrderInterface;
use Magento\PaymentServicesPaypal\Api\Data\PaymentOrderDetailsInterface;

interface PaymentOrderManagementInterface
{
    /**
     * Create a payment order
     *
     * @param string $methodCode
     * @param string $paymentSource
     * @param int $cartId
     * @param string $location
     * @param bool $vaultIntent
     * @param int|null $customerId
     * @param string|null $threeDSMode
     *
     * @return PaymentOrderInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function create(
        string $methodCode,
        string $paymentSource,
        int $cartId,
        string $location,
        bool $vaultIntent = false,
        ?int $customerId = null,
        ?string $threeDSMode = null
    );

    /**
     * Get a payment order
     *
     * @param int $cartId
     * @param string $id
     * @param int|null $customerId
     *
     * @return PaymentOrderDetailsInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function get(
        int $cartId,
        string $id,
        ?int $customerId = null
    );

    /**
     * Sync payment order
     *
     * @param int $cartId
     * @param string $id
     * @param int|null $customerId
     *
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function sync(
        int $cartId,
        string $id,
        ?int $customerId = null
    );
}
