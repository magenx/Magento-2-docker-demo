<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesConnector\Setup;

use Magento\Framework\App\Cache\Type\Config as CacheConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var string[]
     */
    private static array $configRequiringEncryption = [
        'services_connector/services_connector_integration/sandbox_api_key',
        'services_connector/services_connector_integration/sandbox_private_key',
        'services_connector/services_connector_integration/production_api_key',
        'services_connector/services_connector_integration/production_private_key',
    ];

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->encryptor = $encryptor;
    }

    /**
     * @inheritdoc
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        foreach (self::$configRequiringEncryption as $path) {
            $value = $this->scopeConfig->getValue($path);
            if (!empty($value)) {
                $encryptedValue = $this->encryptor->encrypt($value);
                $this->configWriter->save($path, $encryptedValue);
            }
        }
        $this->cacheTypeList->cleanType(CacheConfig::TYPE_IDENTIFIER);
    }
}
