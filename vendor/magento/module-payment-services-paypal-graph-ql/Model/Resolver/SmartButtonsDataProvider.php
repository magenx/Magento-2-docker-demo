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

namespace Magento\PaymentServicesPaypalGraphQl\Model\Resolver;

use Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface;

class SmartButtonsDataProvider implements AdditionalDataProviderInterface
{
    private const PATH_ADDITIONAL_DATA = 'payment_services_paypal_smart_buttons';

    /**
     * @inheritdoc
     */
    public function getData(array $data): array
    {
        if (isset($data[self::PATH_ADDITIONAL_DATA])) {
            return $data[self::PATH_ADDITIONAL_DATA];
        }
        return [];
    }
}
