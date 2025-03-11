<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesId\Plugin;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\ServicesId\Model\MerchantRegistryClient;
use Magento\ServicesId\Model\ServicesConfig;
use Magento\ServicesId\Model\ServicesConfigInterface;

/**
 * Plugin to set cloud id to merchant registry on config save
 */
class SetCloudIdOnConfigSave
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
     * Set cloud id to merchant registry if environment id has changed via onboarding UI
     *
     * @param WriterInterface $subject
     * @param WriterInterface|null $result
     * @param string $path
     * @param string|null $value
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(WriterInterface $subject, ?WriterInterface $result, string $path, ?string $value) : void
    {
        if ($path === ServicesConfig::CONFIG_PATH_ENVIRONMENT_ID) {
            $environmentId = $value;
            $cloudId = $this->servicesConfig->getCloudId();
            if (!empty($environmentId) && !empty($cloudId)) {
                $this->registry->setCloudId($environmentId, $cloudId);
            }
        }
    }
}
