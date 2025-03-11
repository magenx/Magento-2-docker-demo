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

namespace Magento\SaaSCommon\Model\Logging;

/**
 * Interface used to provide custom log handlers defined in di.xml
 */
interface SaaSExportLoggerInterface extends \Psr\Log\LoggerInterface
{
    /**
     * Pass environment variable "EXPORTER_EXTENDED_LOG" to enable extended logging, for example:
     * EXPORTER_EXTENDED_LOG=1 bin/magento saas:resync --feed=products
     *
     * To enable extended logs permanently, you may add "'EXPORTER_EXTENDED_LOG' => 1" to app/etc/env.php
     *
     * Payload will be stored in var/log/saas-export.log
     *
     * In case error happened, data will be stored in var/log/saas-export-errors.log in format:
     * reason, url, base_uri, response and in case of logs extending will add headers and payload
     */
    public const EXPORTER_EXTENDED_LOG = 'EXPORTER_EXTENDED_LOG';
}
