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

namespace Magento\PaymentServicesPaypal\Helper;

use Magento\PaymentServicesPaypal\Model\Config;
use Magento\PaymentServicesPaypal\Model\HostedFieldsConfigProvider;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

class OrderHelper
{
    /**
     * Payment sources that require L2/L3 data
     */
    private const L2_L3_PAYMENT_SOURCES = [
        HostedFieldsConfigProvider::CC_SOURCE,
        HostedFieldsConfigProvider::VAULT_SOURCE
    ];

    /**
     * @var L2DataProvider
     */
    private L2DataProvider $l2DataProvider;

    /**
     * @var L3DataProvider
     */
    private L3DataProvider $l3DataProvider;

    /**
     * @var LineItemsProvider
     */
    private LineItemsProvider $lineItemsProvider;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param L2DataProvider $l2DataProvider
     * @param L3DataProvider $l3DataProvider
     * @param LineItemsProvider $lineItemsProvider
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        L2DataProvider $l2DataProvider,
        L3DataProvider $l3DataProvider,
        LineItemsProvider $lineItemsProvider,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->l2DataProvider = $l2DataProvider;
        $this->l3DataProvider = $l3DataProvider;
        $this->lineItemsProvider = $lineItemsProvider;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Format the amount with two decimal places
     *
     * @param float $amount
     * @return string
     */
    public function formatAmount(float $amount): string
    {
        return number_format((float)$amount, 2, '.', '');
    }

    /**
     * Get L2 data for the given cart
     *
     * Only certain payment sources support L2 data
     *
     * @param Quote $quote
     * @param string $paymentSource
     * @return array
     */
    public function getL2Data(Quote $quote, string $paymentSource): array
    {
        return $this->isL2L3DataApplicable($paymentSource)
            ? $this->l2DataProvider->getL2Data($quote)
            : [];
    }

    /**
     * Get L3 data for the given cart
     *
     * Only certain payment sources support L3 data
     *
     * @param Quote $quote
     * @param string $paymentSource
     * @return array
     */
    public function getL3Data(Quote $quote, string $paymentSource): array
    {
        return $this->isL2L3DataApplicable($paymentSource)
            ? $this->l3DataProvider->getL3Data($quote)
            : [];
    }

    /**
     * Reserve and get the order increment ID
     *
     * @param Quote $quote
     * @return string
     */
    public function reserveAndGetOrderIncrementId(Quote $quote): string
    {
        $quote->reserveOrderId();
        return $quote->getReservedOrderId();
    }

    /**
     * Get line items information for the given cart
     *
     * If the line items total does not match the quote subtotal and tax amount
     * return an empty array to avoid validation error when updating the order in Paypal
     *
     * @param Quote $quote
     * @param string $orderIncrementId
     * @return array
     */
    public function getLineItems(Quote $quote, string $orderIncrementId): array
    {
        $lineItems = $this->lineItemsProvider->getLineItems($quote);

        if ($this->hasLineItemsAmountMismatch($lineItems, $quote, $orderIncrementId)) {
            return [];
        }

        return $lineItems;
    }

    /**
     * Get amount breakdown for the given cart
     *
     * If the amount breakdown total does not match the quote grand total
     * return an empty array to avoid validation error when updating the order in Paypal
     *
     * @param Quote $quote
     * @param string $orderIncrementId
     * @return array
     */
    public function getAmountBreakdown(Quote $quote, string $orderIncrementId): array
    {
        $address = $this->getQuoteAddress($quote);

        $baseSubtotal = (float)$quote->getBaseSubtotal();
        $shippingAmount = (float)$address->getBaseShippingAmount() + (float)$address->getBaseShippingTaxAmount();
        $taxAmount = (float)$address->getBaseTaxAmount() - (float)$address->getBaseShippingTaxAmount();
        $discountAmount = (float)$address->getBaseDiscountAmount();

        if ($this->hasBreakdownAmountMismatch(
            $baseSubtotal,
            $shippingAmount,
            $taxAmount,
            $discountAmount,
            (float)$quote->getBaseGrandTotal(),
            $orderIncrementId
        )) {
            return [];
        }

        return [
            'item_total' => [
                'value' => $this->formatAmount($baseSubtotal),
                'currency_code' => $quote->getCurrency()->getBaseCurrencyCode()
            ],
            'shipping' => [
                'value' => $this->formatAmount($shippingAmount),
                'currency_code' => $quote->getCurrency()->getBaseCurrencyCode()
            ],
            'tax_total' => [
                'value' => $this->formatAmount($taxAmount),
                'currency_code' => $quote->getCurrency()->getBaseCurrencyCode()
            ],
            'discount' => [
                'value' => $this->formatAmount(abs($discountAmount)),
                'currency_code' => $quote->getCurrency()->getBaseCurrencyCode()
            ]
        ];
    }

    /**
     * Check if L2/L3 data are applicable to the order
     *
     * @param string $paymentSource
     * @return bool
     */
    private function isL2L3DataApplicable(string $paymentSource): bool
    {
        return $this->config->isL2L3SendDataEnabled() && $this->isSupportedPaymentSource($paymentSource);
    }

    /**
     * Check if the payment source supports L2/L3 data
     *
     * @param string $paymentSource
     * @return bool
     */
    private function isSupportedPaymentSource(string $paymentSource): bool
    {
        return in_array($paymentSource, self::L2_L3_PAYMENT_SOURCES);
    }

    /**
     * Get the quote address
     *
     * @param Quote $quote
     * @return Quote\Address
     */
    private function getQuoteAddress(Quote $quote): Quote\Address
    {
        $address = $quote->getShippingAddress();
        if ($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        }

        return $address;
    }

    /**
     * Check if the line items total matches the quote subtotal and tax amount
     *
     * @param array $lineItems
     * @param Quote $quote
     * @param string $orderIncrementId
     * @return bool
     */
    private function hasLineItemsAmountMismatch(array $lineItems, Quote $quote, string $orderIncrementId): bool
    {
        $itemTotal = 0;
        $taxTotal = 0;

        foreach ($lineItems as $lineItem) {
            $itemTotal += $this->lineItemsProvider->toCents((float)$lineItem['unit_amount']['value'])
                * (int)$lineItem['quantity'];

            $taxTotal += $this->lineItemsProvider->toCents((float)$lineItem['tax']['value'])
                * (int)$lineItem['quantity'];
        }

        $address = $this->getQuoteAddress($quote);
        $quoteItemsTaxAmount = $this->lineItemsProvider->toCents((float)$address->getBaseTaxAmount()) -
            $this->lineItemsProvider->toCents((float)$address->getBaseShippingTaxAmount());

        if ($itemTotal !== $this->lineItemsProvider->toCents((float)$quote->getBaseSubtotal()) ||
            $taxTotal !== $quoteItemsTaxAmount) {
            $this->logger->info(
                'Line items total does not match quote subtotal or tax amount',
                [
                    'order_increment_id' => $orderIncrementId,
                    'line_items_total' => $itemTotal,
                    'line_items_tax_total' => $taxTotal,
                    'quote_subtotal' => $quote->getBaseSubtotal(),
                    'quote_tax_amount' => $address->getBaseTaxAmount()
                ]
            );

            return true;
        }

        return false;
    }

    /**
     * Check if the amount breakdown total matches the quote grand total
     *
     * @param float $baseSubtotal
     * @param float $shippingAmount
     * @param float $taxAmount
     * @param float $discountAmount
     * @param float $quoteGrandTotal
     * @param string $orderIncrementId
     * @return bool
     */
    private function hasBreakdownAmountMismatch(
        float $baseSubtotal,
        float $shippingAmount,
        float $taxAmount,
        float $discountAmount,
        float $quoteGrandTotal,
        string $orderIncrementId
    ): bool {
        $breakdownTotal = $this->lineItemsProvider->toCents($baseSubtotal) +
            $this->lineItemsProvider->toCents($shippingAmount) +
            $this->lineItemsProvider->toCents($taxAmount) +
            $this->lineItemsProvider->toCents($discountAmount);

        if ($breakdownTotal !== $this->lineItemsProvider->toCents($quoteGrandTotal)) {
            $this->logger->info(
                'Amount breakdown total does not match quote grand total',
                [
                    'order_increment_id' => $orderIncrementId,
                    'breakdown_total' => $breakdownTotal,
                    'breakdown_base_subtotal' => $baseSubtotal,
                    'breakdown_shipping' => $shippingAmount,
                    'breakdown_tax' => $taxAmount,
                    'breakdown_discount' => $discountAmount,
                    'quote_grand_total' => $quoteGrandTotal
                ]
            );

            return true;
        }

        return false;
    }
}
