<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesId\Model;

/**
 * Interface for admin SaaS service calls
 *
 * This API is designed to improve communication experience around services-connector
 * by providing better parsing and improving messaging of permission errors from the API gateway.
 *
 * This interface is intended for use only within Admin area
 * and even in this case its usage should be limited to scenarios where performance is not critical.
 *
 * The implementation makes extra calls to the API gateway to verify keys ON EVERY REQUEST by design, and this greatly affects
 * performance for scenarios where this behavior is not required.
 * Please use this interface only if you are sure that you need this extra validation.
 *
 * Otherwise, you can avoid this by using \Magento\ServicesConnector\Api\ClientResolverInterface directly.
 * You still know about authorization errors by checking for 403 response code.
 *
 * @see \Magento\ServicesConnector\Api\ClientResolverInterface for pure API client without additional key validation
 * @api
 */
interface ServicesClientInterface
{
    /**
     * Execute call to SaaS service
     *
     * @param string $method
     * @param string $uri
     * @param string|null $data
     * @param array $headers
     * @param string|null $environmentOverride
     * @param string|null $hostnameOverride
     * @return array
     */
    public function request(
        string $method,
        string $uri,
        ?string $data = null,
        array $headers = [],
        ?string $environmentOverride = null,
        ?string $hostnameOverride = ''
    ) : array;
}
