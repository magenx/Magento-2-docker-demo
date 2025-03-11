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

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Phrase;

/**
 * Pool of metadata objects
 */
class MetadataPool
{
    /**
     * @var RequestMetadataInterface[]
     */
    private array $metadata = [];

    /**
     * @param RequestMetadataInterface[] $metadata
     * @throws InvalidArgumentException
     */
    public function __construct(array $metadata)
    {
        foreach ($metadata as $metadataObject) {
            if (!$metadataObject instanceof RequestMetadataInterface) {
                throw new InvalidArgumentException(
                    new Phrase(
                        'Instance of "%1" is expected, got "%2" instead.',
                        [
                            RequestMetadataInterface::class,
                            get_class($metadataObject)
                        ]
                    )
                );
            }
        }
        $this->metadata = $metadata;
    }

    /**
     * Return a list of registered metadata objects.
     *
     * @return RequestMetadataInterface[]
     */
    public function getAll(): array
    {
        return $this->metadata;
    }
}
