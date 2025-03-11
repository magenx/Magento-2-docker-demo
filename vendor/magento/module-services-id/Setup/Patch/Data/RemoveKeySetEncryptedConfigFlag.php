<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesId\Setup\Patch\Data;

use Magento\Framework\App\Cache\Type\Config as CacheConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RemoveKeySetEncryptedConfigFlag implements DataPatchInterface
{
    private const CONFIG_PATH_KEY_SET_ENCRYPTED = 'services_connector/services_connector_integration/key_set_encrypted';

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
    private $cacheTypeList;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @inheritDoc
     */
    public function apply(): DataPatchInterface
    {
        if ($this->scopeConfig->isSetFlag(self::CONFIG_PATH_KEY_SET_ENCRYPTED)) {
            $this->configWriter->delete(self::CONFIG_PATH_KEY_SET_ENCRYPTED);
            $this->cacheTypeList->cleanType(CacheConfig::TYPE_IDENTIFIER);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
