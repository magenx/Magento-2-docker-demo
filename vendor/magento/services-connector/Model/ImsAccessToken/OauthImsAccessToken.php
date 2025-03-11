<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesConnector\Model\ImsAccessToken;

use GuzzleHttp\Psr7\Request;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\ServicesConnector\Api\ImsAccessTokenInterface;
use Magento\ServicesConnector\Exception\ImsTokenExchangeException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\ClientInterface;

class OauthImsAccessToken implements ImsAccessTokenInterface
{
    protected const CACHE_IDENTIFIER = 'sc-ims-access-token-';
    /**
     * @var ImsCredentials
     */
    protected $imsCredentials;

    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * OauthImsAccessToken constructor
     *
     * @param ImsCredentials $imsCredentials
     * @param JsonSerializer $jsonSerializer
     * @param ClientInterface $client
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(
        ImsCredentials $imsCredentials,
        JsonSerializer $jsonSerializer,
        ClientInterface $client,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->imsCredentials = $imsCredentials;
        $this->jsonSerializer = $jsonSerializer;
        $this->client = $client;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function getExchangeToken(string $imsEndpoint, array $scopes = []): string
    {
        $scopeSet = implode(",", $this->getScopes($scopes));
        $cacheIdentifier = $this->getCacheIdentifier($scopeSet);
        $cachedToken = $this->cache->load($cacheIdentifier);
        if ($cachedToken) {
            $cachedToken = $this->jsonSerializer->unserialize($cachedToken);
            if ($this->isValidToken($cachedToken, $scopeSet)) {
                return $cachedToken['access_token'];
            }
        }

        $data = $this->requestAccessToken($imsEndpoint, $scopeSet);

        //By the moment using lifetime from IMS less 1 minute
        $expiresIn = $data['expires_in'] - 60;
        $cacheData = [
            'access_token' => $data['access_token'],
            'expires_at' => time() + $expiresIn,
            'scope_list' => $scopeSet,
        ];
        $this->cache->save($this->jsonSerializer->serialize($cacheData), $cacheIdentifier, [], $expiresIn);

        return $data['access_token'];
    }

    /**
     * Builds the scopes' set for exchanging the token.
     *
     * If provided by the caller uses this list, otherwise gets the ones defined in config
     *
     * @param array $scopes
     * @return array
     */
    private function getScopes(array $scopes = []): array
    {
        $scopesSet = array_unique(
            $scopes ?:
                array_map('trim', explode(",", $this->imsCredentials->getScopes()))
        );
        sort($scopesSet);
        return $scopesSet;
    }

    /**
     * Logs error
     *
     * @param ResponseInterface $response
     * @param array $data
     * @param ?string $errorMessage
     * @return void
     * @throws ImsTokenExchangeException
     */
    private function raiseError(ResponseInterface $response, array $data, ?string $errorMessage): void
    {
        $logError = sprintf('An error occurred retrieving IMS access token: %s ', $errorMessage);
        $this->logger->error(
            $logError,
            ['response_data' => $data, 'request_id' => $response->getHeader('X-Request-Id')]
        );
        throw new ImsTokenExchangeException((string)__($logError));
    }

    /**
     * Checks if the cached token is valid
     *
     * @param array $cachedToken
     * @param string $scopeSet
     * @return bool
     */
    private function isValidToken(array $cachedToken, string $scopeSet): bool
    {
        return isset($cachedToken['access_token'], $cachedToken['expires_at'], $cachedToken['scope_list']) &&
            ($cachedToken['expires_at'] > time()) &&
            ($cachedToken['scope_list'] == $scopeSet);
    }

    /**
     * Gets the cache identifier (prefix + scopes list hashed)
     *
     * @param string $scopeSet
     * @return string
     */
    private function getCacheIdentifier(string $scopeSet): string
    {
        return  (self::CACHE_IDENTIFIER . hash('crc32c', $scopeSet));
    }

    /**
     * Requests access token to IMS
     *
     * @param string $endpoint
     * @param string $scopeSet
     * @return array
     * @throws ImsTokenExchangeException
     * @throws ClientExceptionInterface
     */
    private function requestAccessToken(string $endpoint, string $scopeSet): array
    {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Cache-Control' => 'no-cache',
            'User-Agent' => "Adobe Commerce"
        ];

        $body = http_build_query([
            "grant_type" => "client_credentials",
            "client_secret" => $this->imsCredentials->getClientSecret(),
            "client_id" => $this->imsCredentials->getClientId(),
            "scope" => $scopeSet
        ]);

        $response = $this->client->send(
            new Request("POST", $endpoint, $headers, $body)
        );

        $data = $this->jsonSerializer->unserialize($response->getBody()->getContents());
        if ($response->getStatusCode() !== 200 || isset($data['error'])) {
            $this->raiseError($response, $data, $data['error']);
        }
        if (empty($data['access_token'])) {
            $this->raiseError($response, $data, "Missing access_token field");
        }
        return $data;
    }
}
