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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;

class LineItemsProvider
{
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var PaypalApiDataFormatter
     */
    private PaypalApiDataFormatter $paypalApiDataFormatter;

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param PaypalApiDataFormatter $paypalApiDataFormatter
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        PaypalApiDataFormatter $paypalApiDataFormatter,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->paypalApiDataFormatter = $paypalApiDataFormatter;
        $this->logger = $logger;
    }

    /**
     * Get line items information
     *
     * @param Quote $quote
     * @param bool $withL3Data
     * @return array
     */
    public function getLineItems(Quote $quote, bool $withL3Data = false): array
    {
        try {
            return $this->extractItems($quote, $withL3Data);
        } catch (\Exception $e) {
            $this->logger->error(
                'Error extracting line items data',
                ['exception' => $e->getMessage()]
            );

            return [];
        }
    }

    /**
     * Convert the amount to integer cents
     *
     * @param float $amount
     * @return int
     */
    public function toCents(float $amount): int
    {
        return (int)round($amount * 100);
    }

    /**
     * Convert the integer cents to float amount
     *
     * @param int $cents
     * @return float
     */
    public function toCurrency(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /**
     * Extract the items from the quote
     *
     * @param Quote $quote
     * @param bool $withL3Data
     * @return array
     *
     * @throws NoSuchEntityException
     */
    private function extractItems(Quote $quote, bool $withL3Data) : array
    {
        return array_merge(...array_map(
            function (QuoteItem $item) use ($quote, $withL3Data) {
                $product = $this->productRepository->getById($item->getProduct()->getId());
                $qty = (int)$item->getQty();
                $lineItems = [];

                $taxAmountCents = $this->toCents((float)$item->getBaseTaxAmount());
                $taxAmountCentsPerItem = intdiv($taxAmountCents, $qty);
                $taxRemainder = $taxAmountCentsPerItem !== 0 ? $taxAmountCents % $taxAmountCentsPerItem : 0;

                $amountCents = $this->toCents((float)$item->getBaseRowTotal());
                $amountCentsPerItem = intdiv($amountCents, $qty);
                $amountRemainder = $amountCentsPerItem !== 0 ? $amountCents % $amountCentsPerItem: 0;

                if ($this->hasRoundingIssue($taxRemainder, $amountRemainder)) {
                    $this->logger->debug(
                        'Found rounding issue in line items, adding a line item to distribute the remainder',
                        [
                            'order_increment_id' => $quote->getReservedOrderId(),
                            'line_items_row_total' => $item->getBaseRowTotal(),
                            'line_items_tax_total' => $item->getBaseTaxAmount(),
                            'line_items_qty' => $qty,
                            'tax_remainder' => $taxRemainder,
                            'unit_remainder' => $amountRemainder
                        ]
                    );

                    $lineItems[] = $this->createLineItem(
                        $quote,
                        $item,
                        $qty - 1,
                        $amountCentsPerItem,
                        $taxAmountCentsPerItem,
                        $product,
                        $withL3Data
                    );

                    // Add a line to distribute the remainder, rounding issue can be on tax, unit amount or both
                    $lineItems[] = $this->createLineItemToBalanceRoundingIssue(
                        $taxRemainder,
                        $taxAmountCentsPerItem,
                        $amountRemainder,
                        $amountCentsPerItem,
                        $item,
                        $quote,
                        $product,
                        $withL3Data
                    );
                } else {
                    $lineItems[] = $this->createLineItem(
                        $quote,
                        $item,
                        $qty,
                        $amountCentsPerItem,
                        $taxAmountCentsPerItem,
                        $product,
                        $withL3Data
                    );
                }

                return $lineItems;
            },
            $quote->getAllVisibleItems()
        ));
    }

    /**
     * Format the description for the given product
     *
     * @param ProductInterface $product
     * @return string
     *
     * @throws NoSuchEntityException
     */
    private function getProductDescription(ProductInterface $product): string
    {
        $description = $product->getShortDescription() ?? $product->getDescription() ?? $product->getName();

        return $this->paypalApiDataFormatter->formatDescription($description);
    }

    /**
     * Check if there is a rounding issue
     *
     * @param int $taxRemainder
     * @param int $unitAmountRemainder
     *
     * @return bool
     */
    private function hasRoundingIssue(int $taxRemainder, int $unitAmountRemainder): bool
    {
        return $taxRemainder > 0 || $unitAmountRemainder > 0;
    }

    /**
     * Create a line item with qty 1 to balance the rounding issue
     *
     * @param int $taxRemainder
     * @param int $taxAmount
     * @param int $unitRemainder
     * @param int $unitAmount
     * @param QuoteItem $item
     * @param Quote $quote
     * @param ProductInterface $product
     * @param bool $withL3Data
     * @return array
     * @throws NoSuchEntityException
     */
    private function createLineItemToBalanceRoundingIssue(
        int $taxRemainder,
        int $taxAmount,
        int $unitRemainder,
        int $unitAmount,
        QuoteItem $item,
        Quote $quote,
        ProductInterface $product,
        bool $withL3Data
    ): array {
        $taxAmountBalance = ($taxRemainder >= 0) ? $taxAmount + $taxRemainder : $taxAmount;
        $unitAmountBalance = ($unitRemainder >= 0) ? $unitAmount + $unitRemainder : $unitAmount;

        return $this->createLineItem(
            $quote,
            $item,
            1,
            $unitAmountBalance,
            $taxAmountBalance,
            $product,
            $withL3Data
        );
    }

    /**
     * Create a line item
     *
     * @param Quote $quote
     * @param QuoteItem $item
     * @param int $qty
     * @param int $amountCentsPerItem
     * @param int $taxCentsPerItem
     * @param ProductInterface $product
     * @param bool $withL3Data
     * @return array
     * @throws NoSuchEntityException
     */
    private function createLineItem(
        Quote $quote,
        QuoteItem $item,
        int $qty,
        int $amountCentsPerItem,
        int $taxCentsPerItem,
        ProductInterface $product,
        bool $withL3Data = false
    ): array {
        $unitAmount = $this->toCurrency($amountCentsPerItem);
        $taxAmount = $this->toCurrency($taxCentsPerItem);

        $lineItem = [
            'name' => $this->paypalApiDataFormatter->formatName($item->getName()),
            'quantity' => (string)$qty,
            'unit_amount' => [
                'value' => $this->paypalApiDataFormatter->formatAmount($unitAmount),
                'currency_code' => $quote->getCurrency()->getBaseCurrencyCode()
            ],
            'tax' => [
                'value' => $this->paypalApiDataFormatter->formatAmount($taxAmount),
                'currency_code' => $quote->getCurrency()->getBaseCurrencyCode()
            ],
            'upc' => [
                'type' => PaypalApiDataFormatter::DEFAULT_UPC_TYPE,
                'code' => $this->paypalApiDataFormatter->formatUPCCode((int)$item->getProduct()->getId()),
            ],
            'description' => $this->getProductDescription($product),
        ];

        if ($withL3Data) {
            $lineItem['commodity_code'] = $this->paypalApiDataFormatter->formatCommodityCode($item->getSku());
            $lineItem['discount_amount'] = [
                'value' => $this->paypalApiDataFormatter->formatAmount(
                    (float)$item->getBaseDiscountAmount() ?? 0.00
                ),
                'currency_code' => $quote->getCurrency()->getBaseCurrencyCode()
            ];
            $lineItem['unit_of_measure'] = PaypalApiDataFormatter::DEFAULT_UNIT_OF_MEASURE;
        } else {
            $lineItem['sku'] = $this->paypalApiDataFormatter->formatSku($item->getSku());
            $lineItem['url'] = $this->paypalApiDataFormatter->formatUrl($product->getProductUrl());
            $lineItem['category'] = $this->paypalApiDataFormatter->formatCategory((bool)$item->getIsVirtual());
        }

        return $lineItem;
    }
}
