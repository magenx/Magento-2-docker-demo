<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesConnector\Model\ImsAccessToken;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ImsCredentials
{
    public const IMS_TECH_ACCOUNT_CLIENT_ID_PATH = 'services_connector/ims_technical_account_credentials/client_id';
    public const IMS_TECH_ACCOUNT_CLIENT_SECRET_PATH =
        'services_connector/ims_technical_account_credentials/client_secret';
    public const IMS_TECH_ACCOUNT_ORGANIZATION_ID_PATH =
        'services_connector/ims_technical_account_credentials/organization_id';
    public const IMS_TECH_ACCOUNT_TECHNICAL_ACCOUNT_ID_PATH =
        'services_connector/ims_technical_account_credentials/technical_account_id';
    public const IMS_TECH_ACCOUNT_SCOPES_PATH = 'services_connector/ims_technical_account_credentials/scopes';

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Returns client Id config
     *
     * @return string
     */
    public function getClientId(): string
    {
        return $this->config->getValue(self::IMS_TECH_ACCOUNT_CLIENT_ID_PATH);
    }

    /**
     * Returns client secret config
     *
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->config->getValue(self::IMS_TECH_ACCOUNT_CLIENT_SECRET_PATH);
    }

    /**
     * Returns Technical account Id config
     *
     * @return string
     */
    public function getTechnicalAccountId(): string
    {
        return $this->config->getValue(self::IMS_TECH_ACCOUNT_TECHNICAL_ACCOUNT_ID_PATH);
    }

    /**
     * Returns Organization Id config
     *
     * @return string
     */
    public function getOrganizationId(): string
    {
        return $this->config->getValue(self::IMS_TECH_ACCOUNT_ORGANIZATION_ID_PATH);
    }

    /**
     * Returns scopes' list config
     *
     * @return string
     */
    public function getScopes(): string
    {
        return $this->config->getValue(self::IMS_TECH_ACCOUNT_SCOPES_PATH);
    }
}
