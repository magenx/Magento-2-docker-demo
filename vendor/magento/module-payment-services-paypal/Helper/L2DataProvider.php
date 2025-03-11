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

use Psr\Log\LoggerInterface;
use Magento\Quote\Model\Quote;

class L2DataProvider
{
    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * @var PaypalApiDataFormatter
     */
    private PaypalApiDataFormatter $paypalApiDataFormatter;

    /**
     * @param PaypalApiDataFormatter $paypalApiDataFormatter
     * @param LoggerInterface $logger
     */
    public function __construct(PaypalApiDataFormatter $paypalApiDataFormatter, LoggerInterface $logger)
    {
        $this->paypalApiDataFormatter = $paypalApiDataFormatter;
        $this->logger = $logger;
    }

    /**
     * Get L2 data for the given cart
     *
     * @param Quote $quote
     * @return array
     */
    public function getL2Data(Quote $quote) : array
    {
        try {
            $totals = $this->getQuoteTotals($quote);

            return [
                'tax_total' => $this->extractTaxTotal($totals, $quote)
            ];
        } catch (\Exception $e) {
            $this->logger->error(
                'Error extracting L2 data',
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
     * Extract the tax total from the quote
     *
     * @param array $totals
     * @param Quote $quote
     *
     * @return array
     */
    private function extractTaxTotal(array $totals, Quote $quote) : array
    {
        $amount = (isset($totals['tax']) && !empty($totals['tax']->getValue()))
            ? (float) $totals['tax']->getValue()
            : 0.00;

        return [
            'value' => $this->paypalApiDataFormatter->formatAmount($amount),
            'currency_code' => $quote->getCurrency()->getBaseCurrencyCode()
        ];
    }
}
