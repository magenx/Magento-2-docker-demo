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

namespace Magento\PaymentServicesPaypal\Api\Data;

interface PaymentConfigItemInterface
{
    public const CODE = 'code';
    public const IS_VISIBLE = 'is_visible';
    public const SDK_PARAMS = 'sdk_params';
    public const SORT_ORDER = 'sort_order';
    public const PAYMENT_INTENT = 'payment_intent';
    public const TITLE = 'title';

    /**
     * Get code
     *
     * @return string
     */
    public function getCode();

    /**
     * Set code
     *
     * @param string $code
     * @return PaymentConfigItemInterface
     */
    public function setCode($code);

    /**
     * Get SDK params
     *
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSdkParamsInterface[]
     */
    public function getSdkParams();

    /**
     * Set SDK params
     *
     * @param \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSdkParamsInterface[] $sdkParams
     * @return PaymentConfigItemInterface
     */
    public function setSdkParams(array $sdkParams);

    /**
     * Get visible
     *
     * @return bool
     */
    public function hasIsVisible();

    /**
     * Set visible
     *
     * @param bool $isVisible
     * @return PaymentConfigItemInterface
     */
    public function setHasIsVisible($isVisible);

    /**
     * Get Sort Order
     *
     * @return string
     */
    public function getSortOrder();

    /**
     * Set Sort Order
     *
     * @param string $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder);
    /**
     * Get payment intent
     *
     * @return string
     */
    public function getPaymentIntent();

    /**
     * Set payment intent
     *
     * @param string $paymentIntent
     * @return $this
     */
    public function setPaymentIntent($paymentIntent);
    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle();

    /**
     * Set Title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle($title);
}
