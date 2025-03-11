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

use Magento\Framework\Exception\NoSuchEntityException;

interface PaymentConfigRequestInterface
{
    /**
     * Get Config.
     *
     * @param string $location sdk location.
     * @return \Magento\PaymentServicesPaypal\Api\PaymentConfigResponseInterface
     * @throws NoSuchEntityException
     * @since 100.1.0
     */
    public function getConfig(string $location);

    /**
     * Get Apple Pay Config.
     *
     * @param string $location sdk location.
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigApplePayInterface
     * @throws NoSuchEntityException
     * @since 100.1.0
     */
    public function getApplePayConfig(string $location);

    /**
     * Get Google Pay Config.
     *
     * @param string $location sdk location.
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigGooglePayInterface
     * @throws NoSuchEntityException
     * @since 100.1.0
     */
    public function getGooglePayConfig(string $location);

    /**
     * Get Smart Buttons Config.
     *
     * @param string $location sdk location.
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSmartButtonsInterface
     * @throws NoSuchEntityException
     * @since 100.1.0
     */
    public function getSmartButtonsConfig(string $location);

    /**
     * Get Hosted Fields Config.
     *
     * @param string $location sdk location.
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentConfigHostedFieldsInterface
     * @throws NoSuchEntityException
     * @since 100.1.0
     */
    public function getHostedFieldsConfig(string $location);
}
