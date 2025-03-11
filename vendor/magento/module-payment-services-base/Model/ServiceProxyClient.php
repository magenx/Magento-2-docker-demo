<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Model;

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use InvalidArgumentException;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\ServicesConnector\Api\ClientResolverInterface;
use Magento\ServicesConnector\Exception\KeyNotFoundException;
use Magento\ServicesConnector\Api\KeyValidationInterface;
use Magento\Framework\App\ResponseInterface;
use Psr\Log\LoggerInterface;
use Magento\ServiceProxy\Model\ServiceProxyClientInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Client to work with generic service proxy controller.
 */
class ServiceProxyClient implements ServiceProxyClientInterface
{
    /**
     * Extension name for Services Connector
     */
    private const EXTENSION_NAME = 'Magento_PaymentServicesBase';

    /**
     * @var ClientResolverInterface
     */
    private $clientResolver;

    /**
     * @var KeyValidationInterface
     */
    private $keyValidator;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int[]
     */
    private $successfulResponseCodes = [200, 201, 202, 204];

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var ?ServiceRouteResolverInterface $serviceRouteResolver
     */
    private $serviceRouteResolver;

    /**
     * @param ClientResolverInterface $clientResolver
     * @param KeyValidationInterface $keyValidator
     * @param Config $config
     * @param LoggerInterface $logger
     * @param HttpResponse $response
     * @param ?ServiceRouteResolverInterface $serviceRouteResolver
     */
    public function __construct(
        ClientResolverInterface $clientResolver,
        KeyValidationInterface $keyValidator,
        Config $config,
        LoggerInterface $logger,
        HttpResponse $response,
        ?ServiceRouteResolverInterface $serviceRouteResolver = null
    ) {
        $this->clientResolver = $clientResolver;
        $this->keyValidator = $keyValidator;
        $this->config = $config;
        $this->logger = $logger;
        $this->response = $response;
        $this->serviceRouteResolver = $serviceRouteResolver ??
            ObjectManager::getInstance()->get(ServiceRouteResolverInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function request(
        string $path,
        string $httpMethod,
        array $headers,
        string $body = ''
    ): ResponseInterface {
        $this->response->clearHeaders();
        try {
            $environment = $this->getEnvironment($headers);
            if (!$this->validateApiKey($environment)) {
                $this->response->setHttpResponseCode(403);
                $this->response->setBody('API Key Validation failed.');
                $this->logger->error('API Key Validation failed.');
                return $this->response;
            }
            $client = $this->clientResolver->createHttpClient(self::EXTENSION_NAME, $environment);
            $options = $this->getOptions($headers, $body, $environment);
            $path = $this->serviceRouteResolver->resolve($path);
            $response = $client->request($httpMethod, $path, $options);
            $this->buildResponse($response);
            $isSuccessful = in_array($response->getStatusCode(), $this->successfulResponseCodes);

            if (!$isSuccessful) {
                $this->logger->error(
                    'An error occurred.',
                    [
                        'request' => [
                            'host' => (string)$client->getConfig('base_uri'),
                            'path' => $path,
                            'headers' => $options['headers'],
                            'method' => $httpMethod,
                            'body' => $body,
                        ],
                        'response' => [
                            'body' => $response->getBody()->getContents(),
                            'statusCode' => $response->getStatusCode(),
                        ],
                    ]
                );
            }
        } catch (KeyNotFoundException $e) {
            $this->response->setHttpResponseCode(403);
            $this->response->setBody('API Key Validation failed.');
            $this->logger->error('API Key Validation failed.', [$e->getMessage()]);
        } catch (GuzzleException | InvalidArgumentException $e) {
            $this->response->setHttpResponseCode(500);
            $this->response->setBody('Internal Server error.');
            $this->logger->error($e->getMessage());
        }

        return $this->response;
    }

    /**
     * Get request options.
     *
     * @param array $headers
     * @param string $body
     * @param string $environment
     * @return array
     */
    private function getOptions(array $headers, string $body, string $environment): array
    {
        $options['headers'] = array_merge(
            $headers,
            [
                'x-mp-merchant-id' => $this->config->getMerchantId($environment),
                'x-saas-id' => $this->config->getServicesEnvironmentId(),
                'x-request-user-agent' => $headers['x-request-user-agent'] ??
                        sprintf('PaymentServices/%s', $this->config->getVersion())
            ]
        );
        $options['body'] = $body;
        return $options;
    }

    /**
     * Validate the API Gateway Key
     *
     * @param string $environment
     * @return bool
     * @throws KeyNotFoundException
     * @throws InvalidArgumentException
     */
    private function validateApiKey(string $environment): bool
    {
        return $this->keyValidator->execute(
            self::EXTENSION_NAME,
            $environment
        );
    }

    /**
     * Get environment from headers or from the config.
     *
     * @param array $headers
     * @return string
     */
    private function getEnvironment(array $headers): string
    {
        return $headers['X-Payment-Services-Environment'] ?? $this->config->getEnvironmentType();
    }

    /**
     * Build response to client.
     *
     * @param HttpResponseInterface $response
     */
    private function buildResponse(HttpResponseInterface $response)
    {
        // Content already loaded at this point and no need to send Transfer-Encoding header to client.
        $response = $response->withoutHeader('Transfer-Encoding');
        $this->response->setBody($response->getBody()->getContents());
        $this->response->setHttpResponseCode($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $value) {
            $this->response->setHeader($name, $value[0], true);
        }
    }
}
