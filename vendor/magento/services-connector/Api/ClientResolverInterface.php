<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ServicesConnector\Api;

use GuzzleHttp\Middleware;
use Magento\ServicesConnector\Exception\ImsTokenExchangeException;
use Magento\ServicesConnector\Exception\PrivateKeySignException;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Provides configured http client for communication with Magento services
 *
 * `production` and `sandbox` are only two types of supported environments
 */
interface ClientResolverInterface
{
    /**
     * Provides a configured HTTP client
     *
     * The client points to api gateway instance, so you need to pass only service specific chunks in URL
     * E.g. https://api.magento.com/service/service_path
     *      \_____predefined______/
     *
     * The client also adds authentication headers(api keys) to every applicable HTTP request
     *
     * @param string $extension
     * @param string $environment (production|sandbox)
     * @param string $hostname if provided allows to override the base URL
     * @param string[] $scopes List of scopes to be included in the request
     * @param Middleware[] $middlewares List of middlewares that allows the user to decorate the client for its own
     * needs. Middleware should be a callable function with an specific format:
     * @see https://docs.guzzlephp.org/en/latest/handlers-and-middleware.html#middleware
     * @throws \InvalidArgumentException
     * @throws PrivateKeySignException
     * @throws ImsTokenExchangeException
     * @throws ClientExceptionInterface
     * @return \GuzzleHttp\Client
     */
    public function createHttpClient(
        string $extension,
        string $environment = 'production',
        string $hostname = '',
        array $scopes = [],
        array $middlewares = []
    );
}
