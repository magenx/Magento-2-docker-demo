<?php
/************************************************************************
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\InstantPurchase;

use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Helper class to resolve the payment method from the instant purchase payment token.
 *
 * Extracts the payment token from the current http request.
 */
class PaymentMethodResolver
{
    private const INSTANT_PURCHASE_PAYMENT_TOKEN = 'instant_purchase_payment_token';

    private RequestInterface $request;
    private Session $customerSession;
    private PaymentTokenManagementInterface $paymentTokenManagement;

    /**
     * @param RequestInterface $request
     * @param Session $customerSession
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     */
    public function __construct(
        RequestInterface $request,
        Session $customerSession,
        PaymentTokenManagementInterface $paymentTokenManagement)
    {
        $this->request = $request;
        $this->customerSession = $customerSession;
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    /**
     * Returns the payment method code or null.
     *
     * @return string|null
     */
    public function resolve() : ?string
    {
        $hash = $this->getPaymentTokenPublicHash();
        if ($hash === null) {
            return null;
        }

        /**
         * Note: should be int|null, but can be string in practice.
         * @var int|string|null $customerId
         */
        $customerId = $this->customerSession->getCustomerId();
        if ($customerId === null) {
            return null;
        }

        $paymentToken = $this->getPaymentToken($hash, $customerId);
        return $paymentToken?->getPaymentMethodCode();
    }

    /**
     * Retrieves the 'instant_purchase_payment_token' http request param as a string.
     *
     * @return string|null
     */
    private function getPaymentTokenPublicHash() : ?string
    {
        $paymentTokenParam = $this->request->getParam(self::INSTANT_PURCHASE_PAYMENT_TOKEN);
        return $paymentTokenParam === null ? null : (string)$paymentTokenParam;
    }

    /**
     * Retrieves the payment vault token by its customer id and public token hash.
     *
     * Caches the result for the given hash + customer id to avoid redundant database queries.
     *
     * @param string $hash
     * @param int|string $customerId
     * @return PaymentTokenInterface|null
     */
    private function getPaymentToken(string $hash, int|string $customerId) : ?PaymentTokenInterface
    {
        $cacheKey = $hash . "/" . $customerId;

        if (!isset($this->tokenCache[$cacheKey])) {
            $token = $this->paymentTokenManagement->getByPublicHash($hash, $customerId);
            $this->tokenCache[$cacheKey] = $token;
        }

        return $this->tokenCache[$cacheKey];
    }

    /**
     * Payment token cache to avoid redundant round trips to database.
     *
     * @var array
     */
    private array $tokenCache = [];

}
