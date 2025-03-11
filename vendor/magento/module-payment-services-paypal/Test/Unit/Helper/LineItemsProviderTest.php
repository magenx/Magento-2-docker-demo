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

namespace Magento\PaymentServicesPaypal\Test\Unit\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PaymentServicesPaypal\Helper\LineItemsProvider;
use Magento\PaymentServicesPaypal\Helper\PaypalApiDataFormatter;
use Magento\Quote\Api\Data\CurrencyInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LineItemsProviderTest extends TestCase
{
    private const PRODUCT_ID_1 = 123;
    private const PRODUCT_ID_2 = 456;

    /**
     * @var LineItemsProvider
     */
    private $lineItemsProvider;

    /**
     * @var MockObject|ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $paypalFormatter = new(PaypalApiDataFormatter::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->lineItemsProvider = new LineItemsProvider($this->productRepository, $paypalFormatter, $logger);
    }

    /**
     * @return void
     */
    public function testGetLineItemsWithOneDigitalAndOnePhysicalItem(): void
    {
        $productWithShortDescription = $this->createProduct(self::PRODUCT_ID_1);
        $productWithDescription = $this->createProduct(self::PRODUCT_ID_2, false);

        $this->productRepositoryWillReturn(
            self::PRODUCT_ID_1,
            self::PRODUCT_ID_2,
            $productWithShortDescription,
            $productWithDescription
        );

        $quoteItemPhysical = $this->createQuoteItem(
            $productWithShortDescription,
            15,
            30,
            4,
            2
        );
        $quoteItemVirtual = $this->createQuoteItem(
            $productWithDescription,
            20,
            40,
            6.28,
            2,
            true
        );

        $quote = $this->createQuoteWithItems([$quoteItemPhysical, $quoteItemVirtual]);

        $lineItems = $this->lineItemsProvider->getLineItems($quote);

        $url = $this->getMagentoBaseUrl();
        $expectedLineItems = [
            [
                'name' => 'name',
                'quantity' => '2',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '15.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '2.00',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000123'
                ],
                'description' => 'short description',
                'url' => sprintf('%scatalog/product/view/id/%s/', $url, self::PRODUCT_ID_1),
                'category' => 'PHYSICAL_GOODS',
            ],
            [
                'name' => 'name',
                'quantity' => '2',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '20.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '3.14',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000456'
                ],
                'description' => 'description',
                'url' => sprintf('%scatalog/product/view/id/%s/', $url, self::PRODUCT_ID_2),
                'category' => 'DIGITAL_GOODS',
            ]
        ];

        $this->assertEquals($expectedLineItems, $lineItems);
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetLineItemsWithRoundingIssueOnTaxAmount(): void
    {
        $productWithShortDescription = $this->createProduct(self::PRODUCT_ID_1);
        $productWithDescription = $this->createProduct(self::PRODUCT_ID_2, false);

        $this->productRepositoryWillReturn(
            self::PRODUCT_ID_1,
            self::PRODUCT_ID_2,
            $productWithShortDescription,
            $productWithDescription
        );

        $quoteItemPhysical = $this->createQuoteItem(
            $productWithShortDescription,
            15,
            60,
            9.30,
            4
        );

        $quoteItemVirtual = $this->createQuoteItem(
            $productWithDescription,
            20,
            40,
            6.27,
            2,
            true
        );

        $quote = $this->createQuoteWithItems([$quoteItemPhysical, $quoteItemVirtual]);

        $lineItems = $this->lineItemsProvider->getLineItems($quote);

        $url = $this->getMagentoBaseUrl();
        $expectedLineItems = [
            [
                'name' => 'name',
                'quantity' => '3',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '15.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '2.32',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000123'
                ],
                'description' => 'short description',
                'url' => $url.'catalog/product/view/id/123/',
                'category' => 'PHYSICAL_GOODS'
            ],
            [
                'name' => 'name',
                'quantity' => '1',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '15.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '2.34',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000123'
                ],
                'description' => 'short description',
                'url' => $url.'catalog/product/view/id/123/',
                'category' => 'PHYSICAL_GOODS'
            ],
            [
                'name' => 'name',
                'quantity' => '1',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '20.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '3.13',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000456'
                ],
                'description' => 'description',
                'url' => $url.'catalog/product/view/id/456/',
                'category' => 'DIGITAL_GOODS'
            ],
            [
                'name' => 'name',
                'quantity' => '1',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '20.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '3.14',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000456'
                ],
                'description' => 'description',
                'url' => $url.'catalog/product/view/id/456/',
                'category' => 'DIGITAL_GOODS'
            ]
        ];

        $this->assertEquals($expectedLineItems, $lineItems);
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetLineItemsWithRoundingIssueOnUnitAmount(): void
    {
        $productWithShortDescription = $this->createProduct(self::PRODUCT_ID_1);
        $productWithDescription = $this->createProduct(self::PRODUCT_ID_2, false);

        $this->productRepositoryWillReturn(
            self::PRODUCT_ID_1,
            self::PRODUCT_ID_2,
            $productWithShortDescription,
            $productWithDescription
        );

        $quoteItemPhysical = $this->createQuoteItem(
            $productWithShortDescription,
            36.37,
            72.73,
            9.30,
            2
        );
        $quoteItemVirtual = $this->createQuoteItem(
            $productWithDescription,
            35.11,
            70.21,
            6.28,
            2,
            true
        );

        $quote = $this->createQuoteWithItems([$quoteItemPhysical, $quoteItemVirtual]);

        $lineItems = $this->lineItemsProvider->getLineItems($quote);

        $url = $this->getMagentoBaseUrl();
        $expectedLineItems = [
            [
                'name' => 'name',
                'quantity' => '1',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '36.36',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '4.65',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000123'
                ],
                'description' => 'short description',
                'url' => $url.'catalog/product/view/id/123/',
                'category' => 'PHYSICAL_GOODS'
            ],
            [
                'name' => 'name',
                'quantity' => '1',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '36.37',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '4.65',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000123'
                ],
                'description' => 'short description',
                'url' => $url.'catalog/product/view/id/123/',
                'category' => 'PHYSICAL_GOODS'
            ],
            [
                'name' => 'name',
                'quantity' => '1',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '35.10',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '3.14',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000456'
                ],
                'description' => 'description',
                'url' => $url.'catalog/product/view/id/456/',
                'category' => 'DIGITAL_GOODS'
            ],
            [
                'name' => 'name',
                'quantity' => '1',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '35.11',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '3.14',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000456'
                ],
                'description' => 'description',
                'url' => $url.'catalog/product/view/id/456/',
                'category' => 'DIGITAL_GOODS'
            ]
        ];

        $this->assertEquals($expectedLineItems, $lineItems);
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetLineItemsWithRoundingIssueOnTaxAndUnitAmount(): void
    {
        $productWithShortDescription = $this->createProduct(self::PRODUCT_ID_1);
        $productWithDescription = $this->createProduct(self::PRODUCT_ID_2, false);

        $this->productRepositoryWillReturn(
            self::PRODUCT_ID_1,
            self::PRODUCT_ID_2,
            $productWithShortDescription,
            $productWithDescription
        );

        $quoteItemPhysical = $this->createQuoteItem(
            $productWithShortDescription,
            36.37,
            72.73,
            6.27,
            2
        );
        $quoteItemVirtual = $this->createQuoteItem(
            $productWithDescription,
            22.87,
            91.49,
            8.51,
            4,
            true
        );

        $quote = $this->createQuoteWithItems([$quoteItemPhysical, $quoteItemVirtual]);

        $lineItems = $this->lineItemsProvider->getLineItems($quote);

        $url = $this->getMagentoBaseUrl();
        $expectedLineItems = [
            [
                'name' => 'name',
                'quantity' => '1',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '36.36',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '3.13',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000123'
                ],
                'description' => 'short description',
                'url' => $url.'catalog/product/view/id/123/',
                'category' => 'PHYSICAL_GOODS'
            ],
            [
                'name' => 'name',
                'quantity' => '1',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '36.37',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '3.14',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000123'
                ],
                'description' => 'short description',
                'url' => $url.'catalog/product/view/id/123/',
                'category' => 'PHYSICAL_GOODS'
            ],
            [
                'name' => 'name',
                'quantity' => '3',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '22.87',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '2.12',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000456'
                ],
                'description' => 'description',
                'url' => $url.'catalog/product/view/id/456/',
                'category' => 'DIGITAL_GOODS'
            ],
            [
                'name' => 'name',
                'quantity' => '1',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '22.88',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '2.15',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000456'
                ],
                'description' => 'description',
                'url' => $url.'catalog/product/view/id/456/',
                'category' => 'DIGITAL_GOODS'
            ]
        ];

        $this->assertEquals($expectedLineItems, $lineItems);
    }

    /**
     * @return void
     */
    public function testGetLineItemsWithRoundingIssueOnOneOfTwoProducts(): void
    {
        $productWithoutRoundingIssue = $this->createProduct(self::PRODUCT_ID_1);
        $productWithDescription = $this->createProduct(self::PRODUCT_ID_2, false);

        $this->productRepositoryWillReturn(
            self::PRODUCT_ID_1,
            self::PRODUCT_ID_2,
            $productWithoutRoundingIssue,
            $productWithDescription,
        );

        $quoteItem1 = $this->createQuoteItem(
            $productWithoutRoundingIssue,
            15,
            30,
            9.30,
            2
        );

        $quoteItem2= $this->createQuoteItem(
            $productWithDescription,
            20,
            40,
            6.27,
            2,
            true
        );

        $quote = $this->createQuoteWithItems([$quoteItem1, $quoteItem2]);

        $lineItems = $this->lineItemsProvider->getLineItems($quote);

        $url = $this->getMagentoBaseUrl();
        $expectedLineItems = [
            [
                'name' => 'name',
                'quantity' => '2',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '15.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '4.65',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000123'
                ],
                'description' => 'short description',
                'url' => $url.'catalog/product/view/id/123/',
                'category' => 'PHYSICAL_GOODS',
            ],
            [
                'name' => 'name',
                'quantity' => '1',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '20.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '3.13',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000456'
                ],
                'description' => 'description',
                'url' => $url.'catalog/product/view/id/456/',
                'category' => 'DIGITAL_GOODS',
            ],
            [
                'name' => 'name',
                'quantity' => '1',
                'sku' => 'sku',
                'unit_amount' => [
                    'value' => '20.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '3.14',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000456'
                ],
                'description' => 'description',
                'url' => $url.'catalog/product/view/id/456/',
                'category' => 'DIGITAL_GOODS',
            ]
        ];

        $this->assertEquals($expectedLineItems, $lineItems);
    }

    /**
     * @return void
     */
    public function testGetLineItemsWithL3Data(): void
    {
        $productWithShortDescription = $this->createProduct(self::PRODUCT_ID_1);
        $productWithDescription = $this->createProduct(self::PRODUCT_ID_2, false);

        $this->productRepositoryWillReturn(
            self::PRODUCT_ID_1,
            self::PRODUCT_ID_2,
            $productWithShortDescription,
            $productWithDescription
        );

        $quoteItemPhysical = $this->createQuoteItem(
            $productWithShortDescription,
            15,
            30,
            4,
            2
        );
        $quoteItemVirtual = $this->createQuoteItem(
            $productWithDescription,
            20,
            40,
            6.28,
            2,
            true
        );

        $quote = $this->createQuoteWithItems([$quoteItemPhysical, $quoteItemVirtual]);

        $lineItems = $this->lineItemsProvider->getLineItems($quote, true);

        $expectedLineItems = [
            [
                'name' => 'name',
                'quantity' => '2',
                'unit_amount' => [
                    'value' => '15.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '2.00',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000123'
                ],
                'description' => 'short description',
                'commodity_code' => 'sku',
                'discount_amount' => [
                    'value' => '6.00',
                    'currency_code' => 'USD'
                ],
                'unit_of_measure' => 'ITM'
            ],
            [
                'name' => 'name',
                'quantity' => '2',
                'unit_amount' => [
                    'value' => '20.00',
                    'currency_code' => 'USD'
                ],
                'tax' => [
                    'value' => '3.14',
                    'currency_code' => 'USD'
                ],
                'upc' => [
                    'type' => 'UPC-A',
                    'code' => '000456'
                ],
                'description' => 'description',
                'commodity_code' => 'sku',
                'discount_amount' => [
                    'value' => '6.00',
                    'currency_code' => 'USD'
                ],
                'unit_of_measure' => 'ITM'
            ]
        ];

        $this->assertEquals($expectedLineItems, $lineItems);
    }

    /**
     * Create a product with id
     *
     * @param int $id
     * @param bool $hasShortDescription
     *
     * @return Product
     */
    private function createProduct(int $id, bool $hasShortDescription = true): Product
    {
        /**
         * @var ProductInterface|MockObject $product
         */
        $product = $this->createMock(Product::class);

        $product->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        $product->expects($this->any())
            ->method('getProductUrl')
            ->willReturn(sprintf('catalog/product/view/id/%s/', $id));

        if ($hasShortDescription) {
            $product->expects($this->any())
                ->method('getName')
                ->willReturn('short description');
        } else {
            $product->expects($this->any())
                ->method('getName')
                ->willReturn('description');
        }

        return $product;
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

    /**
     * Create a quote item
     *
     * @param Product $product
     * @param float $unitPrice
     * @param float $rowTotal
     * @param float $taxAmount
     * @param int $qty
     * @param bool $isVirtual
     *
     * @return Item
     */
    private function createQuoteItem(
        ProductInterface $product,
        float $unitPrice,
        float $rowTotal,
        float $taxAmount,
        int $qty = 1,
        bool $isVirtual = false,
    ): Quote\Item {
        $quoteItem = $this->getMockBuilder(Quote\Item::class)
            ->setMethods([
                    'getBaseTaxAmount',
                    'getProduct',
                    'getQty',
                    'getName',
                    'getSku',
                    'getBasePrice',
                    'getBaseRowTotal',
                    'getBaseDiscountAmount',
                    'getIsVirtual'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);

        $quoteItem->expects($this->any())
            ->method('getQty')
            ->willReturn($qty);

        $quoteItem->expects($this->any())
            ->method('getBasePrice')
            ->willReturn($unitPrice);

        $quoteItem->expects($this->any())
            ->method('getBaseRowTotal')
            ->willReturn($rowTotal);

        $quoteItem->expects($this->any())
            ->method('getBaseDiscountAmount')
            ->willReturn("6.00");

        // the quote item tax amount is always the total for the row
        $quoteItem->expects($this->any())
            ->method('getBaseTaxAmount')
            ->willReturn($taxAmount);

        $quoteItem->expects($this->any())
            ->method('getName')
            ->willReturn('name');

        $quoteItem->expects($this->any())
            ->method('getSku')
            ->willReturn('sku');

        $quoteItem->expects($this->any())
            ->method('getIsVirtual')
            ->willReturn($isVirtual);

        return $quoteItem;
    }

    /**
     * Create a quote with items
     *
     * @param Quote\Item[] $quoteItems
     *
     * @return Quote
     */
    private function createQuoteWithItems(array $quoteItems): Quote
    {
        $currency = $this->createCurrency();
        $quote = $this->createMock(Quote::class);

        $quote->expects($this->any())
            ->method('getCurrency')
            ->willReturn($currency);

        $quote->expects($this->any())
            ->method('getAllVisibleItems')
            ->willReturn($quoteItems);

        return $quote;
    }

    /**
     * Set the product repository to return the given products
     *
     * @param int $product1Id
     * @param int $product2Id
     * @param Product $productWithShortDescription
     * @param Product $productWithDescription
     *
     * @return void
     */
    private function productRepositoryWillReturn(
        int $product1Id,
        int $product2Id,
        ProductInterface $productWithShortDescription,
        ProductInterface $productWithDescription
    ): void {
        $this->productRepository->expects($this->exactly(2))
            ->method('getById')
            ->willReturnCallback(function ($id) use (
                $product1Id,
                $product2Id,
                $productWithShortDescription,
                $productWithDescription
            ) {
                if ($id === $product1Id) {
                    return $productWithShortDescription;
                } elseif ($id === $product2Id) {
                    return $productWithDescription;
                }
                return null;
            });
    }

    /**
     * Get the Magento base URL
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function getMagentoBaseUrl(): string
    {
        return "";
    }
}
