<?php
/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
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
 **************************************************************************/
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Api;

use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigApplePayInterface;
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigGooglePayInterface;
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigHostedFieldsInterface;
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSmartButtonsInterface;

/**
 * Payment SDK Management interface.getConfig(string$location,
 *
 * @api
 * @since 100.1.0
 */
interface PaymentConfigManagementInterface
{
    /**
     * Get payment sdk params.
     *
     * @param string $location sdk location.
     * @param int|null $store store.
     * @return array
     * @since 100.1.0
     */
    public function getConfig(string $location, ?int $store = null): array;

    /**
     * Get ConfigItem.
     *
     * @param string $location sdk location.
     * @param string $methodCode payment method code.
     * @param int|null $store store.
     * @return PaymentConfigHostedFieldsInterface|PaymentConfigApplePayInterface|PaymentConfigGooglePayInterface|PaymentConfigSmartButtonsInterface
     * @since 100.1.0
     */
    public function getConfigItem(string $location, string $methodCode, ?int $store = null):
        PaymentConfigHostedFieldsInterface |
        PaymentConfigApplePayInterface |
        PaymentConfigGooglePayInterface |
        PaymentConfigSmartButtonsInterface;
}
