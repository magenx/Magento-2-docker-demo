<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServiceProxy\Controller\Adminhtml\Config;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPatchActionInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Cache\Type\Config as CacheConfig;
use Magento\PageCache\Model\Cache\Type as PageCacheConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\PaymentServicesPaypal\Model\SdkService;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ServiceProxy\Controller\Adminhtml\AbstractProxyController;
use Magento\Store\Model\ScopeInterface;
use Exception;

/**
 * Config Provider
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Index extends AbstractProxyController implements
    HttpGetActionInterface,
    HttpPatchActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_ServiceProxy::services';
    private const CONFIG_CACHE_PARAM_TYPE = 'config';
    private const PAGE_CACHE_PARAM_TYPE = 'page';
    private const ALL_CACHE_PARAM_TYPE = 'all';
    private const IS_DEFAULT_SUFFIX = '/isDefault';

    /**
     * @var configPaths
     */
    private $configPaths;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var cacheInvalidationPatterns
     */
    private $cacheInvalidationPatterns;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var TypeListInterface
     */
    private $typeList;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param WriterInterface $configWriter
     * @param JsonFactory $jsonFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $serializer
     * @param TypeListInterface $typeList
     * @param StoreManagerInterface $storeManager
     * @param CacheInterface $cache
     * @param array $configPaths
     * @param array $cacheInvalidationPatterns
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        JsonFactory $jsonFactory,
        ScopeConfigInterface $scopeConfig,
        Json $serializer,
        TypeListInterface $typeList,
        StoreManagerInterface $storeManager,
        CacheInterface $cache,
        array $configPaths = [],
        array $cacheInvalidationPatterns = []
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->jsonFactory = $jsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        $this->typeList = $typeList;
        $this->storeManager = $storeManager;
        $this->cache = $cache;
        $this->configPaths = $configPaths;
        $this->cacheInvalidationPatterns = $cacheInvalidationPatterns;
    }

    /**
     * Retrieve and update service configurations remotely
     *
     * @return ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute(): ResultInterface
    {
        $request = $this->getRequest();
        $method = $request->getMethod();
        $version = $request->getHeader('x-proxy-version') ?: 'v1';
        $clearCache = $request->getParam('clearcache') ? : null;
        $scope = $request->getParam('scope') ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeCode = $request->getParam('scopecode') ?: null;
        $paths = $request->getParam('paths') ?: null;
        $service = $request->getParam('service');
        $response = $this->jsonFactory->create();
        if (!array_key_exists($service, $this->configPaths)) {
            return $response->setHttpResponseCode(404)->setData('Service not defined');
        }
        if ($method === 'PATCH') {
            try {
                $config = $this->serializer->unserialize($request->getContent());
                if ($version === 'v1') {
                    $this->saveConfig($config, $service, $scope, $scopeCode);
                    $this->handleInvalidateCache($config);
                    $this->handleCacheClear(self::CONFIG_CACHE_PARAM_TYPE);
                } else {
                    $this->saveConfigWithDefaults($config, $service, $scope, $scopeCode);
                    $this->handleInvalidateCache($config);
                    $this->handleCacheClear($clearCache);
                }
                $this->handleCustomCacheClear();
            } catch (Exception $e) {
                return $response->setHttpResponseCode(500)
                    ->setData('Failed to save configuration, ' . $e->getMessage());
            }
            return $response->setHttpResponseCode(200)->setData('Configuration updated');
        }
        return $response->setData($this->getConfig($service, $scope, $scopeCode, $paths));
    }

    /**
     * Build a configuration map of provided service
     *
     * @param string $service
     * @param string $scope
     * @param string|null $scopeCode
     * @param string|null $paths
     * @return array
     */
    private function getConfig(string $service, string $scope, ?string $scopeCode, ?string $paths): array
    {
        $scopeCodes = [];
        if ($scopeCode === '*') {
            $scopeCodes = $this->getAllScopeCodes($scope);
        } elseif ($scopeCode) {
            $scopeCodes = array_filter(explode(',', $scopeCode), 'strlen');
        }
        if (count($scopeCodes) > 1 || $scopeCode === '*') {
            $config = [];

            foreach ($scopeCodes as $code) {
                $config[$code] = $this->buildConfigMap($service, $scope, $code, $paths);
            }

            return $config;
        }
        return $this->buildConfigMap($service, $scope, $scopeCode, $paths);
    }

    /**
     * Build a configuration map of provided service
     *
     * @param string $service
     * @param string $scope
     * @param string|null $scopeCode
     * @param string|null $paths
     * @return array
     */
    private function buildConfigMap(string $service, string $scope, ?string $scopeCode, ?string $paths): array
    {
        $configMap = [];
        $paths = $paths ? explode(',', $paths) : $this->configPaths[$service];
        $configPaths = array_intersect($this->configPaths[$service], $paths);
        foreach ($configPaths as $path) {
            $configMap[$path] = $this->scopeConfig->getValue($path, $scope, $scopeCode);
        }
        return $configMap;
    }

    /**
     * Get all scope codes.
     *
     * @param string $scope
     * @return array
     */
    private function getAllScopeCodes(string $scope): array
    {
        $scopeCodes = [];
        if ($scope ===  ScopeInterface::SCOPE_WEBSITES) {
            $scopes = $this->storeManager->getWebsites();
        } elseif ($scope ===  ScopeInterface::SCOPE_GROUPS) {
            $scopes = $this->storeManager->getGroups();
        } else {
            $scopes = $this->storeManager->getStores();
        }

        foreach ($scopes as $scopeValue) {
            $scopeCodes[] = $scopeValue->getCode();
        }

        return $scopeCodes;
    }

    /**
     * Save config
     *
     * @param array $config
     * @param string $service
     * @param string $scope
     * @param string|null $scopeCode
     * @return void
     */
    private function saveConfig(array $config, string $service, string $scope, ?string $scopeCode): void
    {
        $scopeId = $this->interpretScopeCode($scope, $scopeCode);
        foreach ($config as $configKey => $configValue) {
            if (in_array($configKey, $this->configPaths[$service])) {
                $this->configWriter->save($configKey, $configValue, $scope, $scopeId);
            }
        }
    }

    /**
     * Save config and account for defaulted params
     *
     * @param array $config
     * @param string $service
     * @param string $scope
     * @param string $scopeCode
     * @return void
     */
    private function saveConfigWithDefaults(array $config, string $service, string $scope, ?string $scopeCode): void
    {
        $scopeId = $this->interpretScopeCode($scope, $scopeCode);
        foreach ($config as $configKey => $configEntity) {
            $configPath = $configKey;
            $candidateDefaultPath = sprintf('%s%s', $configKey, self::IS_DEFAULT_SUFFIX);
            $isDefault = false;

            // we encounter path and have a default value set to true
            if (array_key_exists($candidateDefaultPath, $config) === true && (bool)$config[$candidateDefaultPath]) {
                continue;
            }

            // we encounter default and have a value set to truee
            if (str_ends_with($configPath, self::IS_DEFAULT_SUFFIX) === true && (bool)$configEntity) {
                $isDefault = true;
                $configPath = substr($configKey, 0, strlen($configKey) - strlen(self::IS_DEFAULT_SUFFIX));
            }

            // path exists in all cases
            if (in_array($configPath, $this->configPaths[$service]) === false) {
                continue;
            }

            // delete defaulted params
            if ($isDefault === true) {
                $this->configWriter->delete($configPath, $scope, $scopeId);
                continue;
            }

            $this->configWriter->save($configPath, $configEntity, $scope, $scopeId);
        }
    }

    /**
     * Handle cache invalidation operations
     *
     * @param array $config
     * @return void
     */
    private function handleInvalidateCache(array $config) : void
    {
        foreach ($this->cacheInvalidationPatterns as $cacheType => $cachePatterns) {
            $cacheTypeInvalidated = false;
            foreach (array_keys($config) as $configKey) {
                if ($cacheTypeInvalidated === false) {
                    $cacheTypeInvalidated = $this->invalidateCacheType($configKey, $cachePatterns, $cacheType);
                } else {
                    break;
                }
            }
        }

        // always invalidate the config cache
        $this->typeList->invalidate(CacheConfig::TYPE_IDENTIFIER);
    }

    /**
     * Invalidate page cache if necessary.
     *
     * @param string $configKey
     * @param array $patterns
     * @param string $type
     * @return bool
     */
    private function invalidateCacheType(string $configKey, array $patterns, string $type) : bool
    {
        foreach ($patterns as $pattern) {
            if (str_starts_with($configKey, $pattern)) {
                $this->typeList->invalidate($type);
                return true;
            }
        }

        return false;
    }

    // phpcs:disable
    /**
     * Interpret the scopeCode passed into the request
     *
     * @param string $scope
     * @param string $scopeCode
     */
    private function interpretScopeCode(string $scope, ?string $scopeCode)
    {
        $scopeId = null;
        if ($scopeCode === null) {
            $scopeId = Store::DEFAULT_STORE_ID;
        } elseif ($scope === ScopeInterface::SCOPE_WEBSITES) {
            $scopeId = $this->storeManager->getWebsite($scopeCode)->getId();
        } elseif ($scope === ScopeInterface::SCOPE_GROUPS) {
            $scopeId = $this->storeManager->getGroup($scopeCode)->getId();
        } else {
            $scopeId = $this->storeManager->getStore($scopeCode)->getId();
        }

        return $scopeId;
    }

    /**
     * Clear cache based on params passed to proxy request
     *
     * @param string $cacheParams
     */
    private function handleCacheClear(?string $cacheParams): void
    {
        if ($cacheParams === null) {
            return;
        }

        $cacheNames = explode(',', $cacheParams);
        // if we are saying we want to clear all we don't care about any other parameters
        if (in_array(self::ALL_CACHE_PARAM_TYPE, $cacheNames) === true) {
            foreach ($this->typeList->getTypes() as $cacheName) {
                $this->typeList->cleanType($cacheName);
            }
            return;
        }

        foreach ($cacheNames as $cacheName) {
            $cacheType = null;
            if ($cacheName === self::CONFIG_CACHE_PARAM_TYPE) {
                $cacheType = CacheConfig::TYPE_IDENTIFIER;
            } elseif ($cacheName === self::PAGE_CACHE_PARAM_TYPE) {
                $cacheType = PageCacheConfig::TYPE_IDENTIFIER;
            } else {
                continue;
            }

            $this->typeList->cleanType($cacheType);
        }
    }

    /**
     * Flush Payment Services custom cache
     *
     * @return void
     */
    private function handleCustomCacheClear(): void
    {
        $this->cache->clean([SdkService::CACHE_TYPE_TAG]);
    }
}
