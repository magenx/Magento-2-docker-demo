<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\PaymentServicesBase\Model\ServiceClientInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\CacheInterface;

class SdkService
{
    private const SDK_ATTRIBUTES = 'sdk-attributes';
    private const SCRIPT_ATTRIBUTES = 'script-attributes';
    private const CURRENCY = 'currency';
    private const LOCALE = 'locale';
    private const PARAM_OBJ_KEY = 'sdk_params';
    private const PAYMENT_OPTIONS = 'payment_options';
    private const IS_REVIEW_PAYMENT_REQUIRED = 'review_payment';
    private const PAYMENT_ACTION = 'payment_action';
    private const PAYMENT_BUILD_SDK_URL_PATH = '/payment/paypal/sdkurl';
    private const CACHE_LIFETIME_KEY = 'data-expires-in';
    public const CACHE_TYPE_IDENTIFIER = 'paypal_sdk_params_payment_services';
    public const CACHE_TYPE_TAG = 'paypal_sdk_params_payment_services_all';
    public const CACHE_LIFETIME = 3600;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ServiceClientInterface
     */
    private $httpClient;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @param Config $config
     * @param ServiceClientInterface $httpClient
     * @param ResolverInterface $localeResolver
     * @param Json $serializer
     * @param StoreManagerInterface $storeManager
     * @param CacheInterface $cache
     */
    public function __construct(
        Config $config,
        ServiceClientInterface $httpClient,
        ResolverInterface $localeResolver,
        Json $serializer,
        StoreManagerInterface $storeManager,
        CacheInterface $cache
    ) {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->localeResolver = $localeResolver;
        $this->serializer = $serializer;
        $this->storeManager = $storeManager;
        $this->cache = $cache;
    }

    /**
     * Get SDK params.
     *
     * @param array $paymentOptions
     * @param bool $isReviewPaymentEnabled
     * @param string $paymentAction
     * @param string|null $websiteId
     * @return array|mixed
     * @throws NoSuchEntityException
     */
    public function getSdkParams(
        array $paymentOptions,
        bool $isReviewPaymentEnabled,
        string $paymentAction,
        ?string $websiteId = null
    ) {
        $currency = $this->storeManager->getStore()->getBaseCurrencyCode();
        $sdkParams = [
            self::PARAM_OBJ_KEY => [
                self::CURRENCY => $currency,
                self::LOCALE => $this->localeResolver->getLocale(),
                self::PAYMENT_OPTIONS => $paymentOptions,
                self::IS_REVIEW_PAYMENT_REQUIRED => $isReviewPaymentEnabled,
                self::PAYMENT_ACTION => $paymentAction
            ]
        ];
        $result = $this->httpClient->request(
            [
                'Content-Type' => 'application/json',
                'x-scope-id' => $websiteId
            ],
            self::PAYMENT_BUILD_SDK_URL_PATH,
            Http::METHOD_POST,
            $this->serializer->serialize($sdkParams)
        );
        if (!$result['is_successful']) {
            return [];
        }
        return $result[self::SDK_ATTRIBUTES][self::SCRIPT_ATTRIBUTES];
    }

    /**
     * Load the SDK Params from cache if exist
     *
     * @param string $location
     * @param string $storeViewId
     * @return array
     */
    public function loadFromSdkParamsCache(string $location, string $storeViewId): array
    {
        $cacheKey = sprintf('%s_%s_%s', self::CACHE_TYPE_IDENTIFIER, $location, $storeViewId);
        $sdkParams = $this->cache->load($cacheKey);
        if ($sdkParams) {
            return $this->serializer->unserialize($sdkParams);
        }
        return [];
    }

    /**
     * Updates the SDK cache based on area (CustomerData/checkout)
     *
     * @param array $result
     * @param string $location
     * @param string $storeViewId
     * @return void
     */
    public function updateSdkParamsCache(array $result, string $location, string $storeViewId)
    {
        $cacheKey = sprintf('%s_%s_%s', self::CACHE_TYPE_IDENTIFIER, $location, $storeViewId);
        $cacheLifetime = $this->getCacheLifetime($result);
        $this->cache->save(
            $this->serializer->serialize($result),
            $cacheKey,
            [self::CACHE_TYPE_TAG],
            $cacheLifetime
        );
    }

    /**
     * Get the cache lifetime value set by checkout service from the PayPal SDK params
     *
     * @param array $sdkParams
     * @return int
     */
    private function getCacheLifetime(array $sdkParams): int
    {
        foreach ($sdkParams as $param) {
            if ($param['name'] === self::CACHE_LIFETIME_KEY) {
                return (int) $param['value'];
            }
        }
        return self::CACHE_LIFETIME;
    }
}
