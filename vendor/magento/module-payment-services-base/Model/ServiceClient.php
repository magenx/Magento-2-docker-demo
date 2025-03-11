<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Model;

use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\PaymentServicesPaypal\Model\SdkService;
use Magento\ServicesConnector\Api\ClientResolverInterface;
use Magento\ServicesConnector\Exception\KeyNotFoundException;
use Magento\ServicesConnector\Api\KeyValidationInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ServicesConnector\Exception\PrivateKeySignException;
use Magento\Framework\App\CacheInterface;

/**
 * Generic SaaS Service Client
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ServiceClient implements ServiceClientInterface
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
     * @var Json
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var State
     */
    private $appState;

    private const SCOPE_TYPE = 'website';

    private const AUTH_REQUEST_EXCEPTION = 'error during authorize request';

    private const NO_ACTIVE_ACCOUNT_EXCEPTION = 'mage id does not have any active PayPal accounts registered';

    /**
     * @var int[]
     */
    private $successfulResponseCodes = [200, 201, 202, 204];

    /**
     * @var ?ServiceRouteResolverInterface $serviceRouteResolver
     */
    private $serviceRouteResolver;

    /**
     * @param ClientResolverInterface $clientResolver
     * @param KeyValidationInterface $keyValidator
     * @param Config $config
     * @param Json $serializer
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param CacheInterface $cache
     * @param State $appState
     * @param ?ServiceRouteResolverInterface $serviceRouteResolver
     */
    public function __construct(
        ClientResolverInterface $clientResolver,
        KeyValidationInterface $keyValidator,
        Config $config,
        Json $serializer,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        CacheInterface $cache,
        State $appState,
        ?ServiceRouteResolverInterface $serviceRouteResolver = null
    ) {
        $this->clientResolver = $clientResolver;
        $this->keyValidator = $keyValidator;
        $this->config = $config;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->cache = $cache;
        $this->appState = $appState;
        $this->serviceRouteResolver = $serviceRouteResolver ??
            ObjectManager::getInstance()->get(ServiceRouteResolverInterface::class);
    }

    /**
     * Make request to service.
     *
     * @param array $headers
     * @param string $path
     * @param string $httpMethod
     * @param string $data
     * @param string $requestContentType
     * @param string $environment
     * @return array
     * @throws NoSuchEntityException
     * @throws PrivateKeySignException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function request(
        array $headers,
        string $path,
        string $httpMethod = Http::METHOD_POST,
        string $data = '',
        string $requestContentType = 'json',
        string $environment = ''
    ): array {
        $result = [];
        try {
            $environment = $environment ?: $this->config->getEnvironmentType();
            $client = $this->clientResolver->createHttpClient(
                self::EXTENSION_NAME,
                $environment
            );
            $options = [
                'headers' => array_merge(
                    $headers,
                    $this->prepareHeaders($headers, $environment)
                ),
                'body' => $data
            ];
            if ($this->validateApiKey($environment)) {
                $path = $this->serviceRouteResolver->resolve($path);
                $response = $client->request($httpMethod, $path, $options);
                $isSuccessful = in_array($response->getStatusCode(), $this->successfulResponseCodes);
                $result = [
                    'is_successful' => $isSuccessful,
                    'status' => $response->getStatusCode()
                ];
                if ($isSuccessful) {
                    if ($requestContentType === 'json') {
                        try {
                            $result = array_merge(
                                $result,
                                $this->serializer->unserialize($response->getBody()->getContents())
                            );
                        } catch (\InvalidArgumentException $e) {
                            $result = [
                                'is_successful' => false,
                                'status' => 500,
                                'message' => $e->getMessage()
                            ];
                            $this->logger->error(
                                'An error occurred.',
                                [
                                    'request' => [
                                        'host' => (string) $client->getConfig('base_uri'),
                                        'path' => $path,
                                        'headers' => $options['headers'],
                                        'method' => $httpMethod,
                                        'body' => $data,
                                    ],
                                    'response' => [
                                        'body' => $response->getBody()->getContents(),
                                        'statusCode' => $response->getStatusCode(),
                                    ],
                                ],
                            );
                        }
                    } else {
                        $result = array_merge(
                            $result,
                            [
                                'content_body' => $response->getBody()->getContents(),
                                'content_disposition' => $response->getHeaderLine('Content-Disposition'),
                                'content_length' => $response->getHeaderLine('Content-Length'),
                                'content_type' => $response->getHeaderLine('Content-Type')
                            ]
                        );
                    }
                } else {
                    $exceptionMessage = $response->getBody()->getContents();
                    $result = [
                        'is_successful' => false,
                        'status' => $response->getStatusCode(),
                        'message' => $exceptionMessage
                    ];
                    if ($exceptionMessage === self::AUTH_REQUEST_EXCEPTION
                        || $exceptionMessage === self::NO_ACTIVE_ACCOUNT_EXCEPTION) {
                        $this->cache->clean([SdkService::CACHE_TYPE_TAG]);
                    }
                    $this->logger->error(
                        'An error occurred.',
                        [
                            'request' => [
                                'host' => (string) $client->getConfig('base_uri'),
                                'path' => $path,
                                'headers' => $options['headers'],
                                'method' => $httpMethod,
                                'body' => $data,
                            ],
                            'response' => [
                                'body' => $response->getBody()->getContents(),
                                'statusCode' => $response->getStatusCode(),
                            ],
                        ]
                    );
                }
            } else {
                $result = [
                    'status' => 403,
                    'is_successful' => false,
                    'statusText' => 'FORBIDDEN',
                    'message' => 'Magento API Key is invalid'
                ];
                $this->logger->error('API Key Validation failed.');
            }
        } catch (KeyNotFoundException $e) {
            $result = [
                'status' => 403,
                'is_successful' => false,
                'statusText' => 'FORBIDDEN',
                'message' => 'Magento API Key not found'
            ];
            $this->logger->error('API Key Validation failed.', [$e->getMessage()]);
        } catch (GuzzleException | InvalidArgumentException $e) {
            $result = [
                'status' => 500,
                'is_successful' => false,
                'statusText' => 'INTERNAL_SERVER_ERROR',
                'message' => 'An error occurred'
            ];
            $this->logger->error($e->getMessage());
        }
        return $result;
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
     * Prepare request headers.
     *
     * @param array $headers
     * @param string $environment
     * @return array
     * @throws NoSuchEntityException
     */
    private function prepareHeaders(array $headers, string $environment): array
    {
        return [
            'x-mp-merchant-id' => $this->config->getMerchantId($environment),
            'x-saas-id' => $this->config->getServicesEnvironmentId(),
            'x-scope-type' => self::SCOPE_TYPE,
            'x-scope-id' => $headers['x-scope-id'] ?? $this->storeManager->getStore()->getWebsiteId(),
            'x-request-user-agent' => $headers['x-request-user-agent'] ??
                    sprintf('PaymentServices/%s/%s', $this->appState->getAreaCode(), $this->config->getVersion())
        ];
    }
}
