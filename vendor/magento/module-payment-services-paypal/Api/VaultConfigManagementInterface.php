<?php
/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
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
 **************************************************************************/
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Api;

use Magento\PaymentServicesPaypal\Model\Api\Data\VaultCreditCardConfig;

/**
 * Payment SDK Management interface.getConfig
 */
interface VaultConfigManagementInterface
{
    /**
     * Get Config for vault
     *
     * @param string $method
     * @param int|null $store store.
     * @return VaultCreditCardConfig
     */
    public function getConfig(string $method, ?int $store = null): VaultCreditCardConfig;
}
