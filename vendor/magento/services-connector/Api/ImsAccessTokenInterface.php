<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesConnector\Api;

use Magento\ServicesConnector\Exception\ImsTokenExchangeException;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Generates an exchange token from Adobe IMS based on the IMS credentials provided
 */
interface ImsAccessTokenInterface
{

    /**
     * Get exchange token from IMS. If not provided endpoint and/or scopes it gets the ones defined in config
     *
     * @param string $imsEndpoint IMS url endpoint
     * @param array $scopes
     * @return string
     * @throws ClientExceptionInterface if an error happens processing the request
     * @throws ImsTokenExchangeException if IMS returns an error
     */
    public function getExchangeToken(string $imsEndpoint, array $scopes = []): string;
}
