<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesId\Model;

use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\ServicesConnector\Api\ClientResolverInterface;
use Magento\ServicesConnector\Exception\KeyNotFoundException;
use Magento\ServicesConnector\Api\KeyValidationInterface;
use Magento\ServicesConnector\Exception\PrivateKeySignException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Saas service request client
 *
 * WARNING: before using this class outside of this module,
 * please consider using the \Magento\ServicesConnector\Api\ClientResolverInterface directly.
 *
 * Check the documentation of the \Magento\ServicesId\Model\ServicesClientInterface for more details.
 */
class ServicesClient implements ServicesClientInterface
{
    /**
     * Config paths
     */
    protected const ENVIRONMENT_CONFIG_PATH = 'magento_saas/environment';

    /**
     * Extension name for Services Connector
     */
    protected const EXTENSION_NAME = 'Magento_ServicesId';

    /**
     * @var ClientResolverInterface
     */
    private $clientResolver;

    /**
     * @var KeyValidationInterface
     */
    private $keyValidator;

    /**
     * @var ScopeConfigInterface
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
     * @param ClientResolverInterface $clientResolver
     * @param KeyValidationInterface $keyValidator
     * @param ScopeConfigInterface $config
     * @param Json $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientResolverInterface $clientResolver,
        KeyValidationInterface $keyValidator,
        ScopeConfigInterface $config,
        Json $serializer,
        LoggerInterface $logger
    ) {
        $this->clientResolver = $clientResolver;
        $this->keyValidator = $keyValidator;
        $this->config = $config;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function request(
        string $method,
        string $uri,
        ?string $data = null,
        array $headers = [],
        ?string $environmentOverride = null,
        ?string $hostnameOverride = ''
    ): array {
        $result = [];
        $environment = $environmentOverride ?: $this->config->getValue(self::ENVIRONMENT_CONFIG_PATH);
        try {
            $client = $this->clientResolver->createHttpClient(
                self::EXTENSION_NAME,
                $environment,
                $hostnameOverride
            );
            $options = [
                'headers' => array_merge(['Content-Type' => 'application/json'], $headers),
                'body' => (string) $data
            ];

            if ($this->validateApiKey($environment)) {
                $response = $client->request($method, $uri, $options);
                $result = $this->parseResponseBody($response);
                if ((isset($result['message']) && ($result['message'] == 'JWT is invalid'))
                    || (isset($result['error']['message']) && $result['error']['message'] == 'Client ID is invalid')) {
                    $result = [
                        'status' => 403,
                        'statusText' => 'FORBIDDEN',
                        'message' => ServicesConfigMessage::ERROR_KEYS_NOT_VALID
                    ];
                }
            } else {
                $result = [
                    'status' => 403,
                    'statusText' => 'FORBIDDEN',
                    'message' => ServicesConfigMessage::ERROR_KEYS_NOT_VALID
                ];
                $this->logger->error(__('API Key Validation Failed'));
            }
        } catch (KeyNotFoundException $ex) {
            $result = [
                'status' => 403,
                'statusText' => 'FORBIDDEN',
                'message' => ServicesConfigMessage::ERROR_KEYS_NOT_VALID
            ];
            $this->logger->error($ex->getMessage());
        } catch (PrivateKeySignException $ex) {
            $result = [
                'status' => 500,
                'statusText' => 'INTERNAL_SERVER_ERROR',
                'message' => ServicesConfigMessage::ERROR_PRIVATE_KEY_SIGN_FAILED
            ];
            $this->logger->error($ex->getMessage());
        } catch (GuzzleException | InvalidArgumentException $ex) {
            $result = [
                'status' => 500,
                'statusText' => 'INTERNAL_SERVER_ERROR',
                'message' => ServicesConfigMessage::ERROR_REQUEST_FAILED
            ];
            $this->logger->error(self::EXTENSION_NAME . ': ' . __('An error occurred contacting Magento Services'));
            $this->logger->error($ex->getMessage());
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
     * @throws PrivateKeySignException
     */
    private function validateApiKey(string $environment) : bool
    {
        return $this->keyValidator->execute(self::EXTENSION_NAME, $environment);
    }

    /**
     * Parse response body from request
     *
     * @param ResponseInterface $response
     * @return array
     */
    private function parseResponseBody(ResponseInterface $response) : array
    {
        $body = $response->getBody()->getContents();
        try {
            $result = $this->serializer->unserialize($body);
        } catch (\InvalidArgumentException $ex) {
            $result = [
                'status' => $response->getStatusCode(),
                'statusText' => $response->getReasonPhrase(),
                'message' => $body
            ];
        }
        return $result;
    }
}
