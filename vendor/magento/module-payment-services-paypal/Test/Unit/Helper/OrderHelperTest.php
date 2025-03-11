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

namespace Magento\PaymentServicesPaypal\Test\Unit\Helper;

use Magento\PaymentServicesPaypal\Helper\L2DataProvider;
use Magento\PaymentServicesPaypal\Helper\L3DataProvider;
use Magento\PaymentServicesPaypal\Helper\LineItemsProvider;
use Magento\PaymentServicesPaypal\Helper\OrderHelper;
use Magento\PaymentServicesPaypal\Model\Config;
use Magento\Quote\Api\Data\CurrencyInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OrderHelperTest extends TestCase
{
    private const ORDER_INCREMENT_ID = '100000001';

    /**
     * @var MockObject|L2DataProvider
     */
    private $l2DataProvider;

    /**
     * @var MockObject|L3DataProvider
     */
    private $l3DataProvider;

    /**
     * @var MockObject|LineItemsProvider
     */
    private $lineItemsProvider;

    /**
     * @var MockObject|Config
     */
    private $config;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var MockObject|OrderHelper
     */
    private $orderHelper;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->l2DataProvider = $this->createMock(L2DataProvider::class);
        $this->l3DataProvider = $this->createMock(L3DataProvider::class);
        $this->lineItemsProvider = $this->createMock(LineItemsProvider::class);
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->orderHelper = new OrderHelper(
            $this->l2DataProvider,
            $this->l3DataProvider,
            $this->lineItemsProvider,
            $this->config,
            $this->logger
        );

        $this->lineItemsProvider->expects($this->any())
            ->method('toCents')
            ->willReturnCallback(
                function (float $amount):int {
                    return (int)($amount * 100);
                }
            );
    }

    /**
     * @return void
     */
    public function testGetLineItemsWithNoAmountMisMatch(): void
    {
        // baseSubtotal should be the sum of the lineItems unit_amount * quantity
        // baseTaxAmount should be the sum of the lineItems tax * quantity
        $quote = $this->createQuote(80.28, 70.00, 10.28);

        $lineItems = [
            [
                'quantity' => '2',
                'unit_amount' => [
                    'value' => '15.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '2.00',
                    'currency_code' => 'USD'
                ],
            ],
            [
                'quantity' => '2',
                'unit_amount' => [
                    'value' => '20.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '3.14',
                    'currency_code' => 'USD'
                ],
            ]
        ];

        $this->lineItemsProvider->expects($this->once())
            ->method('getLineItems')
            ->with($quote)
            ->willReturn($lineItems);

        $this->lineItemsProvider->expects($this->any())
            ->method('toCents')
            ->willReturnCallback(
                function (float $amount):int {
                    return intval($amount * 100);
                }
            );

        $expectedLineItems = $this->orderHelper->getLineItems($quote, self::ORDER_INCREMENT_ID);

        $this->logger->expects($this->never())
            ->method('info');

        $this->assertEquals($expectedLineItems, $lineItems);
    }

    /**
     * @return void
     */
    public function testGetLineItemsWithTaxAmountMisMatch(): void
    {
        // baseSubtotal should be the sum of the lineItems unit_amount * quantity
        // baseTaxAmount should be the sum of the lineItems tax * quantity
        $quote = $this->createQuote(80.00, 70.00, 10.00);

        $lineItems = [
            [
                'quantity' => '2',
                'unit_amount' => [
                    'value' => '15.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '2.00',
                    'currency_code' => 'USD'
                ],
            ],
            [
                'quantity' => '2',
                'unit_amount' => [
                    'value' => '20.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '3.14',
                    'currency_code' => 'USD'
                ],
            ]
        ];

        $this->lineItemsProvider->expects($this->once())
            ->method('getLineItems')
            ->with($quote)
            ->willReturn($lineItems);

        $expectedLineItems = $this->orderHelper->getLineItems($quote, self::ORDER_INCREMENT_ID);

        $this->assertEmpty($expectedLineItems);
    }

    /**
     * @return void
     */
    public function testGetLineItemsWithAmountMisMatch(): void
    {
        // baseSubtotal should be the sum of the lineItems unit_amount * quantity
        // baseTaxAmount should be the sum of the lineItems tax * quantity
        $quote = $this->createQuote(84.28, 74.00, 10.28);

        $lineItems = [
            [
                'quantity' => '2',
                'unit_amount' => [
                    'value' => '15.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '2.00',
                    'currency_code' => 'USD'
                ],
            ],
            [
                'quantity' => '2',
                'unit_amount' => [
                    'value' => '20.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '3.14',
                    'currency_code' => 'USD'
                ],
            ]
        ];

        $this->lineItemsProvider->expects($this->once())
            ->method('getLineItems')
            ->with($quote)
            ->willReturn($lineItems);

        $expectedLineItems = $this->orderHelper->getLineItems($quote, self::ORDER_INCREMENT_ID);

        $this->assertEmpty($expectedLineItems);
    }

    /**
     * @return void
     */
    public function testGetAmountBreakdownWithoutMisMatch(): void
    {
        $quote = $this->createQuote(96.00, 74.00, 10.00, 10.00, 2.00);

        $expectedAmountBreakdown = [
            'item_total' => [
                'value' => '74.00',
                'currency_code' => 'USD'
            ],
            'shipping' => [
                'value' => '10.00',
                'currency_code' => 'USD'
            ],
            'tax_total' => [
                'value' => '10.00',
                'currency_code' => 'USD'
            ],
            'discount' => [
                'value' => '2.00',
                'currency_code' => 'USD'
            ],
        ];

        $amountBreakdown = $this->orderHelper->getAmountBreakdown($quote, self::ORDER_INCREMENT_ID);

        $this->assertEquals($expectedAmountBreakdown, $amountBreakdown);
    }

    /**
     * @return void
     */
    public function testGetAmountBreakdownWithMisMatch(): void
    {
        $quote = $this->createQuote(98.00, 74.00, 10.00, 12.00, 3.00);

        $amountBreakdown = $this->orderHelper->getAmountBreakdown($quote, self::ORDER_INCREMENT_ID);

        $this->assertEmpty($amountBreakdown);
    }

    /**
     * Create a quote
     *
     * @param float $quoteGrandTotal
     * @param float $quoteSubtotal
     * @param float $addressTaxAmount
     * @param float $addressShippingAmount
     * @param float $addressDiscountAmount
     * @return Quote
     */
    private function createQuote(
        float $quoteGrandTotal,
        float $quoteSubtotal,
        float $addressTaxAmount,
        float $addressShippingAmount = 10.00,
        float $addressDiscountAmount = 2.00,
    ): Quote {
        $currency = $this->createCurrency();

        $address = $this->getMockBuilder(Quote\Address::class)
            ->addMethods([
                'getBaseTaxAmount',
                'getBaseShippingAmount',
                'getBaseDiscountAmount',
                'getBaseShippingTaxAmount',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $address->expects($this->any())
            ->method('getBaseTaxAmount')
            ->willReturn($addressTaxAmount);

        $address->expects($this->any())
            ->method('getBaseShippingAmount')
            ->willReturn($addressShippingAmount);

        $address->expects($this->any())
            ->method('getBaseDiscountAmount')
            ->willReturn($addressDiscountAmount);

        $address->expects($this->any())
            ->method('getBaseShippingTaxAmount')
            ->willReturn(0.00);

        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods([
                'getBaseSubtotal',
                'getBaseTaxAmount',
                'getBaseGrandTotal',
            ])
            ->onlyMethods([
                'getShippingAddress',
                'isVirtual',
                'getCurrency',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $quote->expects($this->any())
            ->method('getCurrency')
            ->willReturn($currency);

        $quote->expects($this->any())
            ->method('getBaseSubtotal')
            ->willReturn($quoteSubtotal);

        $quote->expects($this->any())
            ->method('getBaseGrandTotal')
            ->willReturn($quoteGrandTotal);

        $quote->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($address);

        $quote->expects($this->any())
            ->method('isVirtual')
            ->willReturn(false);

        return $quote;
    }

    /**
     * Create a currency
     *
     * @return CurrencyInterface
     */
    private function createCurrency(): CurrencyInterface
    {
        $currency = $this->createMock(CurrencyInterface::class);
        $currency->expects($this->any())
            ->method('getBaseCurrencyCode')
            ->willReturn('USD');

        return $currency;
    }
}
