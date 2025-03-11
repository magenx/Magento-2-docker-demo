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

namespace Magento\PaymentServicesPaypal\Model\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSdkParamsInterface;
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSdkParamsInterfaceFactory;
use Magento\PaymentServicesPaypal\Api\PaymentSdkManagementInterface;
use Magento\PaymentServicesPaypal\Api\VaultConfigManagementInterface;
use Magento\PaymentServicesPaypal\Model\Api\Data\VaultCreditCardConfig;
use Magento\PaymentServicesPaypal\Model\Vault\ConfigProviderPool;
use Magento\Store\Model\StoreManagerInterface;

class VaultConfigManagement implements VaultConfigManagementInterface
{
    public const LOCATION = 'VAULT';
    public const SDK_PARAMS = 'sdk_params';

    /**
     * @var ConfigProviderPool
     */
    private $configProviderPool;

    /**
     * @var PaymentSdkManagementInterface
     */
    private $paymentSdkManagement;

    /**
     * @var PaymentConfigSdkParamsInterfaceFactory
     */
    private $paymentConfigSdkParamsFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param ConfigProviderPool $configProviderPool
     * @param StoreManagerInterface $storeManager
     * @param PaymentConfigSdkParamsInterfaceFactory $paymentConfigSdkParamsFactory
     * @param PaymentSdkManagementInterface $paymentSdkManagement
     */
    public function __construct(
        ConfigProviderPool $configProviderPool,
        StoreManagerInterface $storeManager,
        PaymentConfigSdkParamsInterfaceFactory $paymentConfigSdkParamsFactory,
        PaymentSdkManagementInterface $paymentSdkManagement,
    ) {
        $this->configProviderPool = $configProviderPool;
        $this->storeManager = $storeManager;
        $this->paymentConfigSdkParamsFactory = $paymentConfigSdkParamsFactory;
        $this->paymentSdkManagement = $paymentSdkManagement;
    }

    /**
     * @inheritdoc
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getConfig(string $method, ?int $store = null): VaultCreditCardConfig
    {
        $configProvider = $this->configProviderPool->getProvider($method);
        $config = $configProvider->getConfig($store);

        // TODO: Move this method to the ConfigProviderInterface
        // The Vault ConfigProvider should be the one to provide the SDK params.
        // For now we keep it based on location as it suits the creditCard method
        // but we should refactor it when we will add more vault methods
        $config->setSdkParams($this->getSdkParams($store));

        return $config;
    }

    /**
     * Get SDK params.
     *
     * @param int $store
     * @return PaymentConfigSdkParamsInterface[]
     */
    private function getSdkParams(int $store): array
    {
        $sdkParams = $this->paymentSdkManagement->getParams(self::LOCATION, $store);

        if (count($sdkParams) > 0 && $sdkParams[0]['params']) {
            $sdkParams = $sdkParams[0]['params'];
        }

        $params = [];
        foreach ($sdkParams as $sdkParamItem) {
            if (isset($sdkParamItem['name']) && isset($sdkParamItem['value'])) {
                $params[] = $this->paymentConfigSdkParamsFactory
                    ->create()
                    ->setName($sdkParamItem['name'])
                    ->setValue($sdkParamItem['value']);
            }
        }

        return $params;
    }
}
