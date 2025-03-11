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

namespace Magento\PaymentServicesPaypal\Api\Data;

/**
 * @api
 */
interface PaymentSdkParamsInterface
{
    public const CODE = 'code';
    public const PARAMS = 'params';

    /**
     * Get the payments sdk code
     *
     * @return string
     */
    public function getCode();

    /**
     * Set the payments sdk code
     *
     * @param string $code
     * @return $this
     */
    public function setCode(string $code);

    /**
     * Get the payments sdk params
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSdkParamsInterface[]
     */
    public function getParams();

    /**
     * Set the payments sdk params
     *
     * @param array $params
     * @return $this
     */
    public function setParams(array $params);
}
