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

namespace Magento\PaymentServicesPaypal\Api;

/**
 * An interface for the REST WebAPI to get payment sdk urls
 *
 * @api
 */
interface PaymentSdkRequestInterface
{
    /**
     * Get payment sdk url by location
     *
     * @param string $location
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentSdkParamsInterface[]
     */
    public function getByLocation(string $location);

    /**
     * Get payment sdk url by location and methodCode
     *
     * @param string $location
     * @param string $methodCode
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentSdkParamsInterface
     */
    public function getByLocationAndMethodCode(string $location, string $methodCode);
}
