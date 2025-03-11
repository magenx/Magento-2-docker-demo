<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ServicesConnector\Model;

use Magento\ServicesConnector\Api\ClientResolverInterface;
use Magento\ServicesConnector\Api\ConfigInterface;
use Magento\ServicesConnector\Api\JwtTokenInterface;
use Magento\ServicesConnector\Exception\CredentialsTypeException;
use Magento\ServicesConnector\Exception\ImsTokenExchangeException;
use Magento\ServicesConnector\Exception\KeyNotFoundException;
use Magento\ServicesConnector\Api\KeyValidationInterface;
use Magento\ServicesConnector\Model\ImsAccessToken\ImsCredentials;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Client resolver implementation
 */
class KeyValidation implements KeyValidationInterface
{
    /**
     * @var EnvironmentFactory
     */
    private $environmentFactory;

    /**
     * @var ClientResolverInterface
     */
    private $clientResolver;

    /**
     * @var JwtTokenInterface $jwtToken
     */
    private $jwtToken;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var ImsCredentials
     */
    private $imsCredentials;

    /**
     * KeyValidation constructor.
     *
     * @param EnvironmentFactory $environmentFactory
     * @param ClientResolverInterface $clientResolver
     * @param JwtTokenInterface $jwtToken
     * @param ConfigInterface $config
     * @param ImsCredentials $imsCredentials
     */
    public function __construct(
        EnvironmentFactory $environmentFactory,
        ClientResolverInterface $clientResolver,
        JwtTokenInterface $jwtToken,
        ConfigInterface $config,
        ImsCredentials $imsCredentials
    ) {
        $this->environmentFactory = $environmentFactory;
        $this->clientResolver = $clientResolver;
        $this->jwtToken = $jwtToken;
        $this->config = $config;
        $this->imsCredentials = $imsCredentials;
    }

    /**
     * @inheritDoc
     */
    public function execute($extension, $environment = 'production'): bool
    {
        $envObject = $this->environmentFactory->create($environment);
        if ($this->config->isImsTokenAuthCredentialsType()) {
            return $this->validateImsToken($extension, $environment);
        } elseif ($this->config->isMagentoJwtAuthCredentialsType()) {
            return $this->validateMagentoJwt($envObject, $extension, $environment);
        }
        return false;
    }

    /**
     * Api keys validation for Magento JWT credentials
     *
     * @param Environment $envObject
     * @param string $extension
     * @param string $environment
     * @return bool
     * @throws KeyNotFoundException
     * @throws CredentialsTypeException
     * @throws ClientExceptionInterface
     */
    private function validateMagentoJwt(Environment $envObject, string $extension, string $environment): bool
    {
        if (empty($envObject->getApiKey())) {
            throw new KeyNotFoundException(__("Api key is not found for extension '$extension'"));
        }

        $client = $this->clientResolver->createHttpClient($extension, $environment);
        $privateKey = $envObject->getPrivateKey();

        $url = '/gateway/apikeycheck';
        if (!empty($privateKey)) {
            // Try to sign private key for validation - throws PrivateKeySignException
            $this->jwtToken->getSignature($privateKey);

            $url = $envObject->getKeyValidationUrl();
        }
        if (!$url) {
            // we don't have a URL for this environment
            return true;
        }
        $result = $client->request('GET', $url);
        if ($result->getStatusCode() !== 200) {
            return false;
        }
        return true;
    }

    /**
     * IMS credentials validation
     *
     * @param string $extension
     * @param string $environment
     * @return bool
     * @throws ImsTokenExchangeException
     * @throws CredentialsTypeException
     * @throws ClientExceptionInterface
     */
    public function validateImsToken(string $extension, string $environment): bool
    {
        if (empty($this->imsCredentials->getClientId())) {
            throw new ImsTokenExchangeException(__(sprintf("Missing Client Id for extension %s", $extension)));
        }
        if (empty($this->imsCredentials->getClientSecret())) {
            throw new ImsTokenExchangeException(__(sprintf("Missing Client secret for extension %s", $extension)));
        }
        if (empty($this->imsCredentials->getOrganizationId())) {
            throw new ImsTokenExchangeException(__(sprintf("Missing Organization Id for extension %s", $extension)));
        }
        //try to create the HttpClient that requests an access token to IMS
        $this->clientResolver->createHttpClient($extension, $environment);
        return true;
    }
}
