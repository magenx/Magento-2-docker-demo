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

use Magento\Framework\App\ProductMetadataInterface;

/**
 * Collects and returns commerce edition and version.
 */
class CommerceEdition implements RequestMetadataInterface
{
    private const COMMUNITY_EDITION = 'ce';
    private const COMMERCE_EDITION = 'ee';
    private const B2B_EDITION = 'b2b';
    /**
     * @var ProductMetadataInterface
     */
    private ProductMetadataInterface $commerceMetadata;

    /**
     * @param ProductMetadataInterface $commerceMetadata
     */
    public function __construct(ProductMetadataInterface $commerceMetadata)
    {
        $this->commerceMetadata = $commerceMetadata;
    }

    /**
     * Collects and returns commerce edition and version.
     *
     * @return array
     */
    public function get(): array
    {
        $commerceEdition = self::COMMERCE_EDITION;

        switch ($this->commerceMetadata->getEdition()) {
            case 'Community':
                $commerceEdition = self::COMMUNITY_EDITION;
                break;
            case 'B2B':
                $commerceEdition = self::B2B_EDITION;
                break;
        }

        return [
            'commerceEdition' => $commerceEdition,
            'commerceVersion' => $this->commerceMetadata->getVersion()
        ];
    }
}
