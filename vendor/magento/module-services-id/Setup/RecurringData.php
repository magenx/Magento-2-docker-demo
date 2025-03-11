<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesId\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\ServicesId\Model\MerchantRegistryClient;
use Magento\ServicesId\Model\ServicesConfigInterface;

class RecurringData implements InstallDataInterface
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
     * Set cloudId to merchant registry record on setup:upgrade
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $environmentId = $this->servicesConfig->getEnvironmentId();
        $cloudId = $this->servicesConfig->getCloudId();
        if (!empty($environmentId) && !empty($cloudId)) {
            $this->registry->setCloudId($environmentId, $cloudId);
        }
    }
}
