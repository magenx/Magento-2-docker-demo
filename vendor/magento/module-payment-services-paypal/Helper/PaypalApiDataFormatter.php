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

class PaypalApiDataFormatter
{
    // See https://developer.paypal.com/docs/api/orders/v2/#orders_create for fields validation rules
    public const DEFAULT_UNIT_OF_MEASURE = 'ITM';
    public const DEFAULT_UPC_TYPE = 'UPC-A';
    public const MAX_COMMODITY_CODE_LENGTH = 12;
    public const MIN_UPC_CODE_LENGTH = 6;
    public const MAX_UPC_CODE_LENGTH = 17;
    public const MAX_NAME_LENGTH = 127;
    public const MAX_DESCRIPTION_LENGTH = 127;
    public const MAX_SKU_LENGTH = 127;
    public const MAX_URL_LENGTH = 2048;

    public const CARRIER_OTHER = 'OTHER';

    private const TYPE_PHYSICAL = 'PHYSICAL';
    private const TYPE_DIGITAL = 'DIGITAL';

    public const LINE_ITEMS_CATEGORIES = [
        self::TYPE_DIGITAL  => 'DIGITAL_GOODS',
        self::TYPE_PHYSICAL   => 'PHYSICAL_GOODS',
        'DONATION' => 'DONATION',
    ];

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
     * Format the commodity code
     *
     * @param string $commodityCode
     * @return string
     */
    public function formatCommodityCode(string $commodityCode): string
    {
        return mb_substr($commodityCode, 0, self::MAX_COMMODITY_CODE_LENGTH);
    }

    /**
     * Format the name
     *
     * @param string $name
     * @return string
     */
    public function formatName(string $name): string
    {
        return mb_substr($name, 0, self::MAX_NAME_LENGTH);
    }

    /**
     * Format sku data
     *
     * @param string $sku
     * @return string
     */
    public function formatSku(string $sku): string
    {
        return mb_substr($sku, 0, self::MAX_SKU_LENGTH);
    }

    /**
     * Format the UPC code with type and value
     *
     * @param int $productId
     * @return string
     */
    public function formatUPCCode(int $productId): string
    {
        $trimmedCode = substr((string)$productId, 0, self::MAX_UPC_CODE_LENGTH);
        return str_pad($trimmedCode, self::MIN_UPC_CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Format the description for the given product
     *
     * @param string $description
     * @return string
     */
    public function formatDescription(string $description): string
    {
        return trim(mb_substr(strip_tags($description), 0, self::MAX_DESCRIPTION_LENGTH));
    }

    /**
     * Format the url
     *
     * @param string $url
     * @return string
     */
    public function formatUrl(string $url): string
    {
        return mb_substr($url, 0, self::MAX_URL_LENGTH);
    }

    /**
     * Map the category based on the item type
     *
     * @param bool $isVirtual
     * @return string
     */
    public function formatCategory(bool $isVirtual): string
    {
        if ($isVirtual) {
            return self::LINE_ITEMS_CATEGORIES[self::TYPE_DIGITAL];
        }

        return self::LINE_ITEMS_CATEGORIES[self::TYPE_PHYSICAL];
    }
}
