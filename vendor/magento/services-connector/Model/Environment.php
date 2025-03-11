<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ServicesConnector\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Client resolver implementation
 */
class Environment
{
    public const PROD_GATEWAY_URL_PATH = 'services_connector/{env}_gateway_url';
    public const MAGI_PROD_GATEWAY_URL_PATH = 'services_connector/{env}_magi_gateway_url';
    public const API_KEY_PATH = 'services_connector/services_connector_integration/{env}_api_key';
    public const PRIVATE_KEY_PATH = 'services_connector/services_connector_integration/{env}_private_key';

    public const KEY_VALIDATION_URL_PATH = 'services_connector/{env}_key_validation_url';
    public const CLOUDFRONT_VALIDATION_DOMAINS_PATH = 'services_connector/cloudfront_validation_domains';
    public const CLOUDFRONT_VALIDATION_URL_PATH = 'services_connector/cloudfront_validation_url';

    /**
     * @var string
     */
    private $environment;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Environment constructor.
     *
     * @param string $environment
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        $environment,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->environment = $environment;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Gateway URL from the environment
     *
     * @return string
     */
    public function getGatewayUrl()
    {
        return $this->scopeConfig->getValue(str_replace(
            '{env}',
            $this->environment,
            self::PROD_GATEWAY_URL_PATH
        ));
    }

    /**
     * This is for backwards compatibility to MAGI.
     *
     * @return string
     */
    public function getFallbackGatewayUrl()
    {
        return $this->scopeConfig->getValue(str_replace(
            '{env}',
            $this->environment,
            self::MAGI_PROD_GATEWAY_URL_PATH
        ));
    }

    /**
     * One key per environment so far
     *
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->scopeConfig->getValue(str_replace(
            '{env}',
            $this->environment,
            self::API_KEY_PATH
        ));
    }

    /**
     * One private key per environment so far
     *
     * @return string|null
     */
    public function getPrivateKey(): ?string
    {
        return $this->scopeConfig->getValue(str_replace(
            '{env}',
            $this->environment,
            self::PRIVATE_KEY_PATH
        ));
    }

    public function getKeyValidationUrl(): ?string
    {
        $cloudfrontValidationDomains = explode(
            ',',
            (string)$this->scopeConfig->getValue(
                self::CLOUDFRONT_VALIDATION_DOMAINS_PATH
            )
        );
        $gatewayDomain = parse_url($this->getGatewayUrl(), PHP_URL_HOST);
        if (in_array($gatewayDomain, $cloudfrontValidationDomains, true)) {
            return $this->scopeConfig->getValue(
                self::CLOUDFRONT_VALIDATION_URL_PATH
            );
        }

        return $this->scopeConfig->getValue(str_replace(
            '{env}',
            $this->environment,
            self::KEY_VALIDATION_URL_PATH
        ));
    }
}
