<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesId\Plugin;

use Magento\Framework\App\Config\Value;
use Magento\ServicesId\Model\MerchantRegistryClient;
use Magento\ServicesId\Model\ServicesConfig;
use Magento\ServicesId\Model\ServicesConfigInterface;

/**
 * Plugin to set cloud id to merchant registry on config import
 * Triggered on app:config:import, config:set
 */
class SetCloudIdOnConfigImport
{
    /**
     * @var ServicesConfigInterface
     */
    private $servicesConfig;

    /**
     * @var MerchantRegistryClient
     */
    private $registry;

    /**
     * @param ServicesConfigInterface $servicesConfig
     * @param MerchantRegistryClient $registry
     */
    public function __construct(
        ServicesConfigInterface $servicesConfig,
        MerchantRegistryClient $registry
    ) {
        $this->servicesConfig = $servicesConfig;
        $this->registry = $registry;
    }

    /**
     * Register merchant if environment id has changed via app:config:import
     *
     * @param Value $subject
     * @param Value $result
     * @return Value
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAfterSave(Value $subject, Value $result) : Value
    {
        $savedConfigPath = $result->getPath();
        if ($savedConfigPath === ServicesConfig::CONFIG_PATH_ENVIRONMENT_ID) {
            $environmentId = $result->getValue();
            $cloudId = $this->servicesConfig->getCloudId();
            if (!empty($environmentId) && !empty($cloudId)) {
                $this->registry->setCloudId($environmentId, $cloudId);
            }
        }
        return $result;
    }
}
