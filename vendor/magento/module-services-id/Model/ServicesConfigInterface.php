<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesId\Model;

/**
 * Interface for SaaS Services configuration values
 *
 * @api
 */
interface ServicesConfigInterface
{
    /**
     * Get Project ID for SaaS Services
     *
     * @return string|null
     */
    public function getProjectId() : ?string;

    /**
     * Get Project Name for SaaS Services
     *
     * @return string|null
     */
    public function getProjectName() : ?string;

    /**
     * Get Environment ID for SaaS Services
     *
     * @return string|null
     */
    public function getEnvironmentId() : ?string;

    /**
     * Get Environment Name for SaaS Services
     *
     * @return string|null
     */
    public function getEnvironmentName() : ?string;

    /**
     * Get Environment Type for SaaS Services
     *
     * @return string|null
     */
    public function getEnvironmentType() : ?string;

    /**
     * Get Sandbox API Key from Services Connector configuration
     *
     * @return string|null
     */
    public function getSandboxApiKey(): ?string;

    /**
     * Get Sandbox Private Key from Services Connector configuration
     *
     * @return string|null
     */
    public function getSandboxPrivateKey(): ?string;

    /**
     * Get Production API Key from Services Connector configuration
     *
     * @return string|null
     */
    public function getProductionApiKey(): ?string;

    /**
     * Get Production Private Key from Services Connector configuration
     *
     * @return string|null
     */
    public function getProductionPrivateKey(): ?string;

    /**
     * Get IMS Organization ID from Services Connector configuration
     *
     * @return string|null
     */
    public function getImsOrganizationId(): ?string;

    /**
     * Get cloud project id from environment variable.
     *
     * @return string|null
     */
    public function getCloudId() : ?string;

    /**
     * Check if API Key is set in Services Connector
     *
     * @return bool
     */
    public function isApiKeySet() : bool;

    /**
     * Get Registry Service API path to use in services client call
     *
     * @param string $uri
     * @return string
     */
    public function getRegistryApiUrl(string $uri) : string;

    /**
     * Set values to store configuration
     *
     * @param array $configs
     * @return void
     */
    public function setConfigValues(array $configs) : void;

    /**
     * Get the list of config fields non editable in admin panel because they have been set up via command line
     *
     * @return array
     */
    public function getDisabledFields() : array;
}
