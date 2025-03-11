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

use Magento\Framework\Serialize\SerializerInterface;

/**
 * Provides metadata header in JSON format
 * Example:
 * service-connector-metadata: {"commerceEdition":"B2B","commerceVersion":"2.4.6"}
 */
class RequestMetadataHeaderProvider
{
    private const METADATA_HEADER_NAME = 'service-connector-metadata';

    /**
     * @var MetadataPool
     */
    private MetadataPool $metadataPool;

    /**
     * @var null|string
     */
    private ?string $headerValue = null;

    private SerializerInterface $serializer;

    /**
     * @param MetadataPool $metadataPool
     * @param SerializerInterface $serializer
     */
    public function __construct(
        MetadataPool $metadataPool,
        SerializerInterface $serializer
    ) {
        $this->metadataPool = $metadataPool;
        $this->serializer = $serializer;
    }

    /**
     * Get Header value
     *
     * @return string
     */
    public function getValue(): string
    {
        if ($this->headerValue === null) {
            $metadata = [];

            foreach ($this->metadataPool->getAll() as $metadataObject) {
                $metadata[] = $metadataObject->get();
            }

            $this->headerValue = $metadata ? $this->serializer->serialize(array_merge(...$metadata)) : '';
        }

        return $this->headerValue;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string
    {
        return self::METADATA_HEADER_NAME;
    }
}
