<?php
/**
 * Copyright 2022 Adobe
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

namespace Magento\SaaSCommon\Model\Http;

/**
 * Represents converter interface for http request and response body.
 *
 * @api
 */
interface ConverterInterface
{
    /**
     * Convert from body
     *
     * @param string $body
     * @return array
     */
    public function fromBody($body) : array;

    /**
     * Convert to body
     *
     * @param array $data
     * @return string
     */
    public function toBody(array $data) : string;

    /**
     * Get content-type header
     *
     * @return string
     */
    public function getContentTypeHeader() : string;

    /**
     * Get media-type header
     *
     * @return string
     */
    public function getContentMediaType() : string;

    /**
     * Get content encoding header
     *
     * @return string
     */
    public function getContentEncoding() : ?string;
}
