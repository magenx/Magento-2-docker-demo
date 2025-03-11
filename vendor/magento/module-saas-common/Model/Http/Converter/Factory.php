<?php
/**
 * Copyright 2024 Adobe
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

/**
 * Factory for the http converter
 */
namespace Magento\SaaSCommon\Model\Http\Converter;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\SaaSCommon\Model\Http\ConverterInterface;
use Magento\SaaSCommon\Model\Logging\SaaSExportLoggerInterface as LoggerInterface;

class Factory
{
    /**
     * Json converter type
     */
    private const TYPE_JSON = 'json';

    /**
     * GZIP converter type
     */
    private const TYPE_GZIP = 'gzip';

    /**
     * Address of request compression configuration
     */
    private const REQUEST_COMPRESSION_CONFIG_PATH = 'commerce_data_export/request_compression';

    /**
     * Map of request converter models
     *
     * @var string[]
     */
    private $converterTypes = [
        self::TYPE_JSON => JsonConverter::class,
        self::TYPE_GZIP => GzipConverter::class
    ];

    private ObjectManagerInterface $objectManager;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Construct
     *
     * @param ObjectManagerInterface $objectManager
     * @param ScopeConfigInterface $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $config,
        LoggerInterface $logger
    ) {
        $this->objectManager = $objectManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Create converter model according to request compression configuration
     *
     * @return ConverterInterface
     */
    public function create(): ConverterInterface
    {
        if ('true' === $this->config->getValue(self::REQUEST_COMPRESSION_CONFIG_PATH)) {
            if (\extension_loaded('zlib')) {
                return $this->objectManager->create($this->converterTypes[self::TYPE_GZIP]);
            }
            $this->logger->warning(
                "The zlib-ext is not loaded. Request body can't be compressed and will proceed with regular json"
            );
        }

        return $this->objectManager->create($this->converterTypes[self::TYPE_JSON]);
    }
}
