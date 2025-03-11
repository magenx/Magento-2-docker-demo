<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ServicesConnector\Api;

/**
 * Provides configuration settings useful for other modules
 */
interface ConfigInterface
{
    /**
     * Returns keys configuration page URL
     *
     * @param string $extension
     * @param string $environment (production|sandbox)
     * @return string
     */
    public function getKeyConfigPage($extension, $environment = 'production');

    /**
     * Return api portal url
     *
     * @return string
     */
    public function getApiPortalUrl();

    /**
     * Return the credentials used: either Magento JWT type or IMS account type
     *
     * @return null|string
     */
    public function getCredentialsType(): ?string;

    /**
     * Checks if Magento JWT is used as credentials authorization
     *
     * @return bool
     */
    public function isMagentoJwtAuthCredentialsType(): bool;

    /**
     * Checks if IMS Token is used as credentials authorization
     *
     * @return bool
     */
    public function isImsTokenAuthCredentialsType(): bool;

    /**
     * Return IMS url
     *
     * @return string
     */
    public function getImsUrl(): string;

    /**
     * Return IMS access token resource path
     *
     * @return string
     */
    public function getImsAccessTokenResource(): string;

    /**
     * Return IMS read organizations resource path
     *
     * @return string
     */
    public function getImsReadOrganizationsResource(): string;

    /**
     * Return IMS access token endpoint
     *
     * @return string
     */
    public function getImsAccessTokenEndpoint(): string;

    /**
     * Return IMS read organizations endpoint
     *
     * @return string
     */ public function getImsReadOrganizationsEndpoint(): string;
}
