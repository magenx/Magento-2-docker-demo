<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ServicesConnector\Model;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\ServicesConnector\Api\ClientResolverInterface;
use Magento\ServicesConnector\Api\ConfigInterface;
use Magento\ServicesConnector\Api\ImsAccessTokenInterface;
use Magento\ServicesConnector\Api\JwtTokenInterface;
use Magento\ServicesConnector\Exception\CredentialsTypeException;
use Magento\ServicesConnector\Model\ImsAccessToken\ImsCredentials;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Client resolver implementation
 */
class ClientResolver implements ClientResolverInterface
{
    /**
     * @var GuzzleClientFactory
     */
    private $clientFactory;

    /**
     * @var EnvironmentFactory
     */
    private $environmentFactory;

    /**
     * @var JwtTokenInterface
     */
    private $jwtToken;

    /**
     * @var ProductMetadata
     */
    private $productMetadata;

    /**
     * @var ImsCredentials
     */
    private $imsCredentialsConfig;

    /**
     * @var ImsAccessTokenInterface
     */
    private $imsAccessToken;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ClientResolver constructor.
     *
     * @param GuzzleClientFactory $clientFactory
     * @param EnvironmentFactory $environmentFactory
     * @param JwtTokenInterface $jwtToken
     * @param ProductMetadataInterface $productMetadata
     * @param ImsCredentials $imsCredentialsConfig
     * @param ImsAccessTokenInterface $imsAccessToken
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        GuzzleClientFactory $clientFactory,
        EnvironmentFactory $environmentFactory,
        JwtTokenInterface $jwtToken,
        ProductMetadataInterface $productMetadata,
        ImsCredentials $imsCredentialsConfig,
        ImsAccessTokenInterface $imsAccessToken,
        ConfigInterface $config,
        LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->environmentFactory = $environmentFactory;
        $this->productMetadata = $productMetadata;
        $this->jwtToken = $jwtToken;
        $this->imsCredentialsConfig = $imsCredentialsConfig;
        $this->imsAccessToken = $imsAccessToken;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function createHttpClient(
        $extension,
        $environment = 'production',
        $hostname = '',
        $scopes = [],
        $middlewares = []
    ) {
        $envObject = $this->environmentFactory->create($environment);

        if ($this->config->isImsTokenAuthCredentialsType()) {
            array_unshift($middlewares, $this->getImsTokenSettingsMiddleware($scopes));
        } elseif ($this->config->isMagentoJwtAuthCredentialsType()) {
            array_unshift($middlewares, $this->getMagentoJwtSettingsMiddleware($envObject));
        } else {
            $this->logger->error('Credentials type not set');
            throw new CredentialsTypeException(__('Credentials type not set'));
        }

        $stack = HandlerStack::create();
        $this->addMiddlewaresToStack($middlewares, $stack);

        return $this->clientFactory->create(
            [
                RequestOptions::HTTP_ERRORS => false,
                'base_uri' => $this->getBaseUri($envObject, $hostname, $extension),
                'handler' => $stack,
                'headers' => ['User-Agent' => $this->getUserAgent()],
            ]
        );
    }

    /**
     * Gets user agent info
     *
     * @return string
     */
    private function getUserAgent(): string
    {
        return sprintf(
            'Magento Services Connector (Magento: %s)',
            $this->productMetadata->getEdition() . ' '
            . $this->productMetadata->getVersion()
        );
    }

    /**
     * Gets Base uri based on config, hostname allows the caller to override the base URL defined in the config
     *
     * @param Environment $envObject
     * @param string $hostname
     * @param string $extension
     * @return string
     */
    private function getBaseUri(Environment $envObject, string $hostname = '', string $extension = ''): string
    {
        if ($this->config->isMagentoJwtAuthCredentialsType() && empty($envObject->getPrivateKey())) {
            // Fall back to MAGI
            return $envObject->getFallbackGatewayUrl();
        }
        return $hostname ?: $envObject->getGatewayUrl();
    }

    /**
     * Builds settings for IMS tech account credentials
     *
     * @param array $scopes
     * @return callable
     * @throws \Magento\ServicesConnector\Exception\ImsTokenExchangeException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    private function getImsTokenSettingsMiddleware(array $scopes = []): callable
    {
        $accessToken = $this->imsAccessToken->getExchangeToken($this->config->getImsAccessTokenEndpoint(), $scopes);
        return Middleware::mapRequest(function (RequestInterface $request) use ($accessToken) {
            return $request
                ->withHeader('Authorization', 'Bearer ' . $accessToken)
                ->withHeader('x-api-key', $this->imsCredentialsConfig->getClientId())
                ->withHeader('x-gw-ims-org-id', $this->imsCredentialsConfig->getOrganizationId());
        });
    }

    /**
     * Builds settings for Magento JWT auth type
     *
     * @param Environment $envObject
     * @return callable
     * @throws \Magento\ServicesConnector\Exception\PrivateKeySignException
     */
    private function getMagentoJwtSettingsMiddleware(Environment $envObject): callable
    {
        $apiKey = $envObject->getApiKey();
        $privateKey = $envObject->getPrivateKey();
        return Middleware::mapRequest(function (RequestInterface $request) use ($apiKey, $privateKey) {
            return $request
                ->withHeader('magento-api-key', $apiKey)
                ->withHeader('x-api-key', $apiKey)
                ->withHeader('x-gw-signature', $this->jwtToken->getSignature($privateKey));
        });
    }

    /**
     * Adds the list of middlewares to the handler stack.
     *
     * @see https://docs.guzzlephp.org/en/latest/handlers-and-middleware.html#middleware
     *
     * @param callable[] $middlewares
     * @param HandlerStack $stack
     * @return void
     */
    private function addMiddlewaresToStack(array $middlewares, HandlerStack $stack): void
    {
        if (!empty($middlewares)) {
            foreach ($middlewares as $middleware) {
                if (is_callable($middleware)) {
                    $stack->push($middleware);
                }
            }
        }
    }
}
