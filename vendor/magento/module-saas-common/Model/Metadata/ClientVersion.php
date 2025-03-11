<?php
/**
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
 */
declare(strict_types=1);

namespace Magento\SaaSCommon\Model\Metadata;

use Magento\Framework\Module\PackageInfo;

/**
 * Get Magento_SaaSCommon module version
 */
class ClientVersion implements RequestMetadataInterface
{
    /**
     * @var PackageInfo
     */
    private PackageInfo $packageInfo;

    /**
     * @param PackageInfo $packageInfo
     */
    public function __construct(PackageInfo $packageInfo)
    {
        $this->packageInfo = $packageInfo;
    }

    /**
     * Collects and returns version of the Magento_SaaSCommon module.
     *
     * @return array
     */
    public function get(): array
    {
        return [
            'saasExporterVersion' => $this->packageInfo->getVersion('Magento_SaaSCommon'),
        ];
    }
}
