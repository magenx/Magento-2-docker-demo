<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ServicesConnector\Model;

use Magento\ServicesConnector\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;

/**
 * Client resolver implementation
 */
class Config implements ConfigInterface
{
    public const CONFIG_PATH_SERVICES_CONNECTOR_CREDENTIALS_TYPE =
        'services_connector/services_connector_credentials/credentials_type';
    public const IMS_TECH_ACCOUNT_IMS_URL_PATH =
        'services_connector/ims_technical_account_credentials/ims_url';
    public const IMS_TECH_ACCOUNT_IMS_ACCESS_TOKEN_RESOURCE_PATH =
        'services_connector/ims_technical_account_credentials/ims_access_token_resource';
    public const IMS_TECH_ACCOUNT_IMS_READ_ORGANIZATIONS_RESOURCE_PATH =
        'services_connector/ims_technical_account_credentials/ims_read_organizations_resource';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $url
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlInterface $url
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->url = $url;
    }

    /**
     * @inheritDoc
     */
    public function getKeyConfigPage($extension, $environment = 'production')
    {
        return $this->url->getUrl(
            'adminhtml/system_config/edit',
            [
                'section' => 'services_connector'
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getApiPortalUrl()
    {
        return $this->scopeConfig->getValue('services_connector/api_portal_url');
    }

    /**
     * @inheritDoc
     */
    public function getCredentialsType(): ?string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_SERVICES_CONNECTOR_CREDENTIALS_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function isMagentoJwtAuthCredentialsType(): bool
    {
        return $this->getCredentialsType() === CredentialsType::MAGENTO_JWT_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function isImsTokenAuthCredentialsType(): bool
    {
        return $this->getCredentialsType() === CredentialsType::IMS_TECH_ACCOUNT_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function getImsUrl(): string
    {
        return $this->scopeConfig->getValue(self::IMS_TECH_ACCOUNT_IMS_URL_PATH);
    }

    /**
     * @inheritDoc
     */
    public function getImsAccessTokenResource(): string
    {
        return $this->scopeConfig->getValue(self::IMS_TECH_ACCOUNT_IMS_ACCESS_TOKEN_RESOURCE_PATH);
    }

    /**
     * @inheritDoc
     */
    public function getImsAccessTokenEndpoint(): string
    {
        return $this->getImsUrl() . $this->getImsAccessTokenResource();
    }

    /**
     * @inheritDoc
     */
    public function getImsReadOrganizationsResource(): string
    {
        return $this->scopeConfig->getValue(self::IMS_TECH_ACCOUNT_IMS_READ_ORGANIZATIONS_RESOURCE_PATH);
    }

    /**
     * @inheritDoc
     */
    public function getImsReadOrganizationsEndpoint(): string
    {
        return $this->getImsUrl() . $this->getImsReadOrganizationsResource();
    }
}
