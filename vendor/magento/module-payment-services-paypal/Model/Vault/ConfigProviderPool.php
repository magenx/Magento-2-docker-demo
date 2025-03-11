<?php
/************************************************************************
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Vault;

use Magento\Framework\Exception\LocalizedException;

class ConfigProviderPool
{
    /**
     * @var ConfigProviderInterface[]
     */
    private $configProviders;

    /**
     * @param ConfigProviderInterface[] $configProviders
     */
    public function __construct(array $configProviders = [])
    {
        $this->configProviders = $configProviders;
    }

    /**
     * Get the appropriate config provider based on the method
     *
     * @param string $method
     * @return ConfigProviderInterface
     * @throws LocalizedException
     */
    public function getProvider(string $method): ConfigProviderInterface
    {
        if (!isset($this->configProviders[$method])) {
            throw new LocalizedException(__('No config provider found for vault method "%1".', $method));
        }

        return $this->configProviders[$method];
    }
}
