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

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * An interface for the REST WebAPI request to create an order
 *
 * @api
 */
interface PaymentOrderRequestInterface
{
    /**
     * Create a payment order for logged in customer
     *
     * @param string $methodCode
     * @param string $paymentSource
     * @param int $cartId
     * @param string $location
     * @param int $customerId
     * @param bool $vaultIntent
     * @param string | null $threeDSMode
     *
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentOrderInterface
     */
    public function create(
        string $methodCode,
        string $paymentSource,
        int $cartId,
        string $location,
        int $customerId,
        bool $vaultIntent = false,
        ?string $threeDSMode = null,
    );

    /**
     * Create a payment order for guest customer
     *
     * @param string $methodCode
     * @param string $paymentSource
     * @param string $cartId
     * @param string $location
     * @param bool $vaultIntent
     * @param string | null $threeDSMode
     *
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentOrderInterface
     */
    public function createGuest(
        string $methodCode,
        string $paymentSource,
        string $cartId,
        string $location,
        bool $vaultIntent = false,
        ?string $threeDSMode = null,
    );

    /**
     * Get payment order for logged in customer
     *
     * @param int $cartId
     * @param int $customerId
     * @param string $id
     *
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentOrderDetailsInterface
     */
    public function get(
        int $cartId,
        int $customerId,
        string $id
    );

    /**
     * Get payment order for guest customer
     *
     * @param string $cartId
     * @param string $id
     *
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentOrderDetailsInterface
     */
    public function getForGuest(
        string $cartId,
        string $id
    );

    /**
     * Sync payment order for logged in customer
     *
     * @param int $cartId
     * @param int $customerId
     * @param string $id
     *
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     *
     * @return bool
     */
    public function sync(
        int $cartId,
        int $customerId,
        string $id
    );

    /**
     * Sync payment order for guest customer
     *
     * @param string $cartId
     * @param string $id
     *
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     *
     * @return bool
     */
    public function syncForGuest(
        string $cartId,
        string $id
    );
}
