<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServiceProxy\Model;

use Magento\Framework\App\ResponseInterface;

interface ServiceProxyClientInterface
{
    /**
     * @param string $path
     * @param string $httpMethod
     * @param array $headers
     * @param string $body
     * @return array
     */
    public function request(
        string $path,
        string $httpMethod,
        array $headers,
        string $body = ''
    ): ResponseInterface;
}
