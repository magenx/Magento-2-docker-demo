<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesId\Model;

use Magento\Config\Model\Config\Reader\Source\Deployed\SettingChecker;
use Magento\Framework\App\Cache\Type\Config as CacheConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class ServicesConfig implements ServicesConfigInterface
{
    /**
     * Config path values for Services Id
     */
    public const CONFIG_PATH_PROJECT_ID = 'services_connector/services_id/project_id';
    public const CONFIG_PATH_PROJECT_NAME = 'services_connector/services_id/project_name';
    public const CONFIG_PATH_ENVIRONMENT_ID = 'services_connector/services_id/environment_id';
    public const CONFIG_PATH_ENVIRONMENT_NAME = 'services_connector/services_id/environment_name';
    public const CONFIG_PATH_ENVIRONMENT_TYPE = 'services_connector/services_id/environment';
    public const CONFIG_PATH_IMS_ORGANIZATION_ID = 'services_connector/services_id/ims_organization_id';
    public const CONFIG_PATH_REGISTRY_API_PATH = 'services_connector/services_id/registry_api_path';

    /**
     * Config path values for Services Connector
     */
    public const CONFIG_PATH_SERVICES_CONNECTOR_ENVIRONMENT = 'magento_saas/environment';
    public const CONFIG_PATH_SERVICES_CONNECTOR_API_KEY =
        'services_connector/services_connector_integration/{env}_api_key';
    public const CONFIG_PATH_SERVICES_CONNECTOR_PRIVATE_KEY =
        'services_connector/services_connector_integration/{env}_private_key';
    public const CONFIG_PATH_SERVICES_CONNECTOR_SANDBOX_API_KEY =
        'services_connector/services_connector_integration/sandbox_api_key';
    public const CONFIG_PATH_SERVICES_CONNECTOR_SANDBOX_PRIVATE_KEY =
        'services_connector/services_connector_integration/sandbox_private_key';
    public const CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_API_KEY =
        'services_connector/services_connector_integration/production_api_key';
    public const CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_PRIVATE_KEY =
        'services_connector/services_connector_integration/production_private_key';

    /**
     * Config fields names
     */
    public const CONFIG_FIELD_SANDBOX_API_KEY = 'sandboxApiKey';
    public const CONFIG_FIELD_SANDBOX_PRIVATE_KEY = 'sandboxPrivateKey';
    public const CONFIG_FIELD_PRODUCTION_API_KEY = 'productionApiKey';
    public const CONFIG_FIELD_PRODUCTION_PRIVATE_KEY = 'productionPrivateKey';
    public const CONFIG_FIELD_PROJECT_ID = 'projectId';
    public const CONFIG_FIELD_PROJECT_NAME = 'projectName';
    public const CONFIG_FIELD_ENVIRONMENT_ID = 'environmentId';
    public const CONFIG_FIELD_ENVIRONMENT_NAME = 'environmentName';
    public const CONFIG_FIELD_ENVIRONMENT_TYPE = 'environmentType';
    public const CONFIG_FIELD_IMS_ORGANIZATION_ID = 'imsOrganizationId';

    /**
     * @var string[]
     */
    public static array $configRequiringEncryption = [
        self::CONFIG_PATH_SERVICES_CONNECTOR_SANDBOX_API_KEY,
        self::CONFIG_PATH_SERVICES_CONNECTOR_SANDBOX_PRIVATE_KEY,
        self::CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_API_KEY,
        self::CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_PRIVATE_KEY,
    ];

    /**
     * @var string[]
     */
    public static array $configFieldsList = [
        self::CONFIG_FIELD_SANDBOX_API_KEY => self::CONFIG_PATH_SERVICES_CONNECTOR_SANDBOX_API_KEY,
        self::CONFIG_FIELD_SANDBOX_PRIVATE_KEY => self::CONFIG_PATH_SERVICES_CONNECTOR_SANDBOX_PRIVATE_KEY,
        self::CONFIG_FIELD_PRODUCTION_API_KEY => self::CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_API_KEY,
        self::CONFIG_FIELD_PRODUCTION_PRIVATE_KEY => self::CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_PRIVATE_KEY,
        self::CONFIG_FIELD_PROJECT_ID => self::CONFIG_PATH_PROJECT_ID,
        self::CONFIG_FIELD_PROJECT_NAME => self::CONFIG_PATH_PROJECT_NAME,
        self::CONFIG_FIELD_ENVIRONMENT_ID => self::CONFIG_PATH_ENVIRONMENT_ID,
        self::CONFIG_FIELD_ENVIRONMENT_NAME => self::CONFIG_PATH_ENVIRONMENT_NAME,
        self::CONFIG_FIELD_ENVIRONMENT_TYPE => self::CONFIG_PATH_ENVIRONMENT_TYPE,
        self::CONFIG_FIELD_IMS_ORGANIZATION_ID => self::CONFIG_PATH_IMS_ORGANIZATION_ID
    ];

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var EncryptorInterface
     */
    protected $keyEncryptor;

    /**
     * @var SettingChecker
     */
    protected $settingChecker;

    /**
     * @param ScopeConfigInterface $config
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param EncryptorInterface $keyEncryptor
     * @param SettingChecker $settingChecker
     */
    public function __construct(
        ScopeConfigInterface $config,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        EncryptorInterface $keyEncryptor,
        SettingChecker $settingChecker
    ) {
        $this->config = $config;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->keyEncryptor = $keyEncryptor;
        $this->settingChecker = $settingChecker;
    }

    /**
     * @inheritdoc
     */
    public function getProjectId() : ?string
    {
        return $this->config->getValue(self::CONFIG_PATH_PROJECT_ID);
    }

    /**
     * @inheritDoc
     */
    public function getProjectName(): ?string
    {
        return $this->config->getValue(self::CONFIG_PATH_PROJECT_NAME);
    }

    /**
     * @inheritDoc
     */
    public function getEnvironmentId() : ?string
    {
        return $this->config->getValue(self::CONFIG_PATH_ENVIRONMENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function getEnvironmentName(): ?string
    {
        return $this->config->getValue(self::CONFIG_PATH_ENVIRONMENT_NAME);
    }

    /**
     * @inheritdoc
     */
    public function getEnvironmentType() : ?string
    {
        return $this->config->getValue(self::CONFIG_PATH_ENVIRONMENT_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function getSandboxApiKey(): ?string
    {
        return $this->config->getValue(self::CONFIG_PATH_SERVICES_CONNECTOR_SANDBOX_API_KEY);
    }

    /**
     * @inheritDoc
     */
    public function getSandboxPrivateKey(): ?string
    {
        return $this->config->getValue(self::CONFIG_PATH_SERVICES_CONNECTOR_SANDBOX_PRIVATE_KEY);
    }

    /**
     * @inheritDoc
     */
    public function getProductionApiKey(): ?string
    {
        return $this->config->getValue(self::CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_API_KEY);
    }

    /**
     * @inheritDoc
     */
    public function getProductionPrivateKey(): ?string
    {
        return $this->config->getValue(self::CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_PRIVATE_KEY);
    }

    /**
     * @inheritdoc
     */
    public function getImsOrganizationId() : ?string
    {
        return $this->config->getValue(self::CONFIG_PATH_IMS_ORGANIZATION_ID);
    }

    /**
     * @inheritDoc
     */
    public function getCloudId(): ?string
    {
        // phpcs:ignore Magento2.Security.Superglobal
        return $_ENV["MAGENTO_CLOUD_PROJECT"] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function isApiKeySet() : bool
    {
        $apiKey = false;
        $privateKey = false;
        $environment = $this->config->getValue(self::CONFIG_PATH_SERVICES_CONNECTOR_ENVIRONMENT);
        if ($environment) {
            $apiKey = $this->config->getValue(str_replace(
                '{env}',
                $environment,
                self::CONFIG_PATH_SERVICES_CONNECTOR_API_KEY
            ));
            $privateKey = $this->config->getValue(str_replace(
                '{env}',
                $environment,
                self::CONFIG_PATH_SERVICES_CONNECTOR_PRIVATE_KEY
            ));
        }
        return $apiKey && $privateKey;
    }

    /**
     * @inheritDoc
     */
    public function getRegistryApiUrl(string $uri) : string
    {
        return $this->config->getValue(self::CONFIG_PATH_REGISTRY_API_PATH) . $uri;
    }

    /**
     * @inheritDoc
     */
    public function setConfigValues(array $configs) : void
    {
        $configChanged = false;
        foreach ($configs as $key => $value) {
            if ($value === null || $value === '') {
                $this->configWriter->delete($key);
                $configChanged = true;
            } else {
                if ($this->config->getValue($key) === $value) {
                    continue;
                }
                if ($this->requiresEncryption($key)) {
                    $value = $this->keyEncryptor->encrypt($value);
                }
                $this->configWriter->save($key, $value);
                $configChanged = true;
            }
        }
        if ($configChanged) {
            $this->cacheTypeList->cleanType(CacheConfig::TYPE_IDENTIFIER);
        }
    }

    /**
     * @inheritDoc
     */
    public function getDisabledFields(): array
    {
        $disabledFieldsList = [];
        foreach (self::$configFieldsList as $field => $path) {
            if ($this->isConfigFieldReadOnly($path)) {
                $disabledFieldsList[] = $field;
            }
        }
        return $disabledFieldsList;
    }

    /**
     * Checks if config fields is read only
     *
     * @param string $path
     * @return bool
     */
    private function isConfigFieldReadOnly(string $path): bool
    {
        return $this->settingChecker->isReadOnly($path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * Checks if the key requires encryption
     *
     * @param string $key
     * @return bool
     */
    private function requiresEncryption(string $key): bool
    {
        return in_array($key, self::$configRequiringEncryption, true);
    }
}
