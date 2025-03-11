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

namespace Magento\SaaSCommon\Model\Http\Converter;

use Magento\SaaSCommon\Model\Http\ConverterInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Represents Gzip converter for http request and response body.
 */
class GzipConverter implements ConverterInterface
{
    /**
     * Media-Type corresponding to this converter.
     */
    public const CONTENT_MEDIA_TYPE = 'application/json';

    /**
     * Content encoding type
     */
    public const CONTENT_ENCODING = 'gzip';

    private Json $serializer;

    /**
     * @param Json $serializer
     */
    public function __construct(Json $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function fromBody($body): array
    {
        $decodedBody = $this->serializer->unserialize($body);
        return $decodedBody ?? [$body];
    }

    /**
     * @inheritdoc
     */
    public function toBody(array $data): string
    {
        if (!\extension_loaded('zlib')) {
            throw new \RuntimeException('PHP extension zlib is required.');
        }
        //phpcs:ignore Magento2.Functions.DiscouragedFunction
        return \gzencode($this->serializer->serialize($data));
    }

    /**
     * @inheritdoc
     */
    public function getContentTypeHeader(): string
    {
        return sprintf('Content-Type: %s', self::CONTENT_MEDIA_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function getContentMediaType(): string
    {
        return self::CONTENT_MEDIA_TYPE;
    }

    /**
     * @inheritdoc
     */
    public function getContentEncoding(): string
    {
        return self::CONTENT_ENCODING;
    }
}
