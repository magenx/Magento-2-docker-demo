<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Model;

interface ServiceClientInterface
{
    /**
     * @param array $headers
     * @param string $path
     * @param string $httpMethod
     * @param string $data
     * @param string $requestContentType
     * @param string $environment
     * @return array
     */
    public function request(
        array $headers,
        string $path,
        string $httpMethod,
        string $data = '',
        string $requestContentType = 'json',
        string $environment = ''
    ): array;
}
