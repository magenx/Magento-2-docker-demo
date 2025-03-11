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

namespace Magento\PaymentServicesPaypal\Model\Api;

use Magento\PaymentServicesPaypal\Api\PaymentOrderManagementInterface;
use Magento\PaymentServicesPaypal\Api\PaymentOrderRequestInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

/**
 * Implementation for the REST WebAPI request to create an order
 *
 * @api
 */
class PaymentOrderRequest implements PaymentOrderRequestInterface
{
    /**
     * @var PaymentOrderManagementInterface
     */
    private PaymentOrderManagementInterface $paymentOrderManagement;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId;

    /**
     * @param PaymentOrderManagementInterface $paymentOrderManagement
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     */
    public function __construct(
        PaymentOrderManagementInterface $paymentOrderManagement,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
    ) {
        $this->paymentOrderManagement = $paymentOrderManagement;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
    }

    /**
     * @inheritdoc
     */
    public function create(
        string $methodCode,
        string $paymentSource,
        int $cartId,
        string $location,
        int $customerId,
        bool $vaultIntent = false,
        ?string $threeDSMode = null
    ) {
        return $this->paymentOrderManagement->create(
            $methodCode,
            $paymentSource,
            $cartId,
            $location,
            $vaultIntent,
            $customerId,
            $threeDSMode
        );
    }

    /**
     * @inheritdoc
     */
    public function createGuest(
        string $methodCode,
        string $paymentSource,
        string $cartId,
        string $location,
        bool $vaultIntent = false,
        ?string $threeDSMode = null
    ) {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        return $this->paymentOrderManagement->create($methodCode, $paymentSource, $cartId, $location, $vaultIntent, null, $threeDSMode);
    }

    /**
     * @inheritdoc
     */
    public function get(
        int $cartId,
        int $customerId,
        string $id
    ) {
        return $this->paymentOrderManagement->get($cartId, $id, $customerId);
    }

    /**
     * @inheritdoc
     */
    public function getForGuest(
        string $cartId,
        string $id
    ) {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        return $this->paymentOrderManagement->get($cartId, $id);
    }

    /**
     * @inheritdoc
     */
    public function sync(
        int $cartId,
        int $customerId,
        string $id
    ) {
        return $this->paymentOrderManagement->sync($cartId, $id, $customerId);
    }

    /**
     * @inheritdoc
     */
    public function syncForGuest(
        string $cartId,
        string $id
    ) {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        return $this->paymentOrderManagement->sync($cartId, $id);
    }
}
