<?php

/**
 * ADOBE CONFIDENTIAL
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
 */

declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Helper;

use \Magento\Store\Model\Information as Config;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\Quote;

class L3DataProvider
{
    /**
     * @var PaypalApiDataFormatter
     */
    private PaypalApiDataFormatter $paypalApiDataFormatter;

    /**
     * @var LineItemsProvider
     */
    private LineItemsProvider $lineItemsProvider;

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * @param PaypalApiDataFormatter $paypalApiDataFormatter
     * @param LineItemsProvider $lineItemsProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        PaypalApiDataFormatter $paypalApiDataFormatter,
        LineItemsProvider $lineItemsProvider,
        LoggerInterface $logger
    ) {
        $this->paypalApiDataFormatter = $paypalApiDataFormatter;
        $this->lineItemsProvider = $lineItemsProvider;
        $this->logger = $logger;
    }

    /**
     * Get L3 data for the given cart
     *
     * @param Quote $quote
     * @return array
     */
    public function getL3Data(Quote $quote): array
    {
        try {
            $totals = $this->getQuoteTotals($quote);

            return [
                'ships_from_postal_code' => $this->extractShipsFromPostalCode($quote),
                'shipping_amount' => $this->extractShippingAmount($totals, $quote),
                'discount_amount' => $this->extractDiscount($totals, $quote),
                'line_items' => $this->lineItemsProvider->getLineItems($quote, true),
            ];
        } catch (\Exception $e) {
            $this->logger->error(
                'Error extracting L3 data',
                ['exception' => $e->getMessage()]
            );

            return [];
        }
    }

    /**
     * Extract the totals from the quote
     *
     * @param Quote $quote
     * @return array
     */
    private function getQuoteTotals(Quote $quote): array
    {
        $quote->collectTotals();

        if ($quote->isVirtual()) {
            $totals = $quote->getBillingAddress()->getTotals();
        } else {
            $totals = $quote->getShippingAddress()->getTotals();
        }

        return $totals;
    }

    /**
     * Extract the ships from postal code from the store configuration
     *
     * @param Quote $quote
     * @return string
     */
    private function extractShipsFromPostalCode(Quote $quote): string
    {
        return $quote->getStore()->getConfig(Config::XML_PATH_STORE_INFO_POSTCODE) ?? '';
    }

    /**
     * Extract the shipping amount from the quote
     *
     * @param array $totals
     * @param Quote $quote
     * @return array
     */
    private function extractShippingAmount(array $totals, Quote $quote) : array
    {
        $amount = (isset($totals['shipping']) && !empty($totals['shipping']->getValue()))
            ? (float) $totals['shipping']->getValue()
            : 0.00;

        return [
            'value' => $this->paypalApiDataFormatter->formatAmount($amount),
            'currency_code' => $quote->getCurrency()->getBaseCurrencyCode()
        ];
    }

    /**
     * Extract the discount amount from the quote
     *
     * @param array $totals
     * @param Quote $quote
     * @return array
     */
    private function extractDiscount(array $totals, Quote $quote) : array
    {
        $amount = (isset($totals['discount']) && !empty($totals['discount']->getValue()))
            ? abs((float) $totals['discount']->getValue())
            : 0.00;

        return [
            'value' => $this->paypalApiDataFormatter->formatAmount($amount),
            'currency_code' => $quote->getCurrency()->getBaseCurrencyCode()
        ];
    }
}
