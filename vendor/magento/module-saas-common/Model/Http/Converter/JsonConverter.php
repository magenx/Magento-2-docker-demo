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
 * Represents JSON converter for http request and response body.
 */
class JsonConverter implements ConverterInterface
{
    /**
     * Media-Type corresponding to this converter.
     */
    public const CONTENT_MEDIA_TYPE = 'application/json';

    /**
     * @var Json
     */
    private $serializer;

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
        return $decodedBody === null ? [$body] : $decodedBody;
    }

    /**
     * @inheritdoc
     */
    public function toBody(array $data): string
    {
        return $this->serializer->serialize($data);
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
     *
     * Return null for Json body type
     */
    public function getContentEncoding(): ?string
    {
        return null;
    }
}
