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

namespace Magento\PaymentServicesPaypal\Plugin;

use Magento\PaymentServicesPaypal\Helper\OrderHelper;
use Magento\PaymentServicesPaypal\Model\Config;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\PaymentServicesPaypal\Model\OrderService;
use Magento\PaymentServicesBase\Model\HttpException;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Cancels an order and an authorization transaction.
 */
class OrderUpdate
{
    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     * @var OrderService
     */
    private OrderService $orderService;

    /**
     * @var array
     */
    private array $orderUpdateLocations;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param OrderService $orderService
     * @param OrderHelper $orderHelper
     * @param LoggerInterface $logger
     * @param array $orderUpdateLocations
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        OrderService            $orderService,
        OrderHelper             $orderHelper,
        LoggerInterface         $logger,
        array                   $orderUpdateLocations = []
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->orderService = $orderService;
        $this->orderUpdateLocations = $orderUpdateLocations;
        $this->logger = $logger;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Updates an order during the order creation.
     *
     * @param CartManagementInterface $subject
     * @param int $cartId
     * @param PaymentInterface|null $payment
     * @return void
     * @throws LocalizedException
     * @throws HttpException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforePlaceOrder(
        CartManagementInterface $subject,
        int $cartId,
        ?PaymentInterface $payment = null
    ) : void {
        $quote = $this->quoteRepository->get($cartId);
        $location = $quote->getPayment()->getAdditionalInformation('location');

        if (in_array($location, $this->orderUpdateLocations)) {
            $quote->getBillingAddress()->setShouldIgnoreValidation(true);
            $quote->getShippingAddress()->setShouldIgnoreValidation(true);
        }

        if ($this->doesRequirePriceUpdate($quote)) {
            $orderIncrementId = $this->orderHelper->reserveAndGetOrderIncrementId($quote);
            $paypalOrderId = $quote->getPayment()->getAdditionalInformation('paypal_order_id');

            try {
                $this->orderService->update(
                    $paypalOrderId,
                    [
                        'amount' => $this->orderHelper->formatAmount((float)$quote->getBaseGrandTotal()),
                        'currency_code' => $quote->getCurrency()->getBaseCurrencyCode(),
                        'line_items' => $this->orderHelper->getLineItems($quote, $orderIncrementId),
                        'amount_breakdown' => $this->orderHelper->getAmountBreakdown($quote, $orderIncrementId),
                    ]
                );
            } catch (HttpException $e) {
                throw new LocalizedException(__('Your payment was not successful. Try again.'));
            }
        }
    }

    /**
     * Checks if the quote requires a price update.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    private function doesRequirePriceUpdate(\Magento\Quote\Api\Data\CartInterface $quote) : bool
    {
        $paymentMethod = (string) $quote->getPayment()?->getMethod();
        if (!$paymentMethod || !str_starts_with($paymentMethod, Config::PAYMENTS_SERVICES_PREFIX)) {
            return false;
        }

        $originalOrderAmount = $quote->getPayment()->getAdditionalInformation('paypal_order_amount');
        if (empty($originalOrderAmount)) {
            return false;
        }

        return $this->areAmountsDifferent((float)$originalOrderAmount, (float)$quote->getBaseGrandTotal());
    }

    /**
     * Checks if the order amounts are different.
     *
     * @param float $originalOrderAmount
     * @param float $newOrderAmount
     * @return bool
     */
    private function areAmountsDifferent(float $originalOrderAmount, float $newOrderAmount) : bool
    {
        return $this->orderHelper->formatAmount($originalOrderAmount)
            !== $this->orderHelper->formatAmount($newOrderAmount);
    }
}
