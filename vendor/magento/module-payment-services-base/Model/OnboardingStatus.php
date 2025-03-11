<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Model;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\CacheInterface;

/**
 * Get onboarding status
 */
class OnboardingStatus
{
    private const STATUS_COMPLETED = 'COMPLETED';

    private const CACHE_TAG = 'PAYMENT_SERVICES_IS_ONBOARDED_';

    private const CACHE_LIFETIME = 3600 * 3;

    /**
     * @var ServiceClientInterface
     */
    private $serviceClient;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param ServiceClientInterface $serviceClient
     * @param Config $config
     * @param CacheInterface $cache
     * @param Json $serializer
     */
    public function __construct(
        ServiceClientInterface $serviceClient,
        Config $config,
        CacheInterface $cache,
        Json $serializer
    ) {
        $this->serviceClient = $serviceClient;
        $this->config = $config;
        $this->cache = $cache;
        $this->serializer = $serializer;
    }

    /**
     * Get onboarding status
     *
     * @param string $environment
     * @return array
     * @throws HttpException
     */
    public function getStatus($environment = '') : array
    {
        $onboardingStatus = $this->requestStatus($environment);
        $this->cache->save(
            $this->serializer->serialize($onboardingStatus['status'] == self::STATUS_COMPLETED),
            self::CACHE_TAG . $environment,
            [],
            self::CACHE_LIFETIME
        );
        return $onboardingStatus;
    }

    /**
     * Check if onboarding is completed
     *
     * @param string $environment
     * @return bool
     * @throws HttpException
     */
    public function isOnboarded($environment) : bool
    {
        try {
            if ($isOnboarded = $this->cache->load(self::CACHE_TAG . $environment)) {
                return $this->serializer->unserialize($isOnboarded);
            }
            $isOnboarded = $this->config->isConfigured($environment) &&
                $this->getStatus($environment)['status'] == self::STATUS_COMPLETED;

        } catch (\Exception $e) {
            $isOnboarded = false;
        }
        $this->cache->save(
            $this->serializer->serialize($isOnboarded),
            self::CACHE_TAG . $environment,
            [],
            self::CACHE_LIFETIME,
        );
        return $isOnboarded;
    }

    /**
     * Set onboarding status
     *
     * @param string $environment
     * @param bool $isOnboarded
     * @throws HttpException
     */
    public function setIsOnboarded($environment, bool $isOnboarded) : void
    {
        $this->cache->save(
            $this->serializer->serialize($isOnboarded),
            self::CACHE_TAG . $environment,
            [],
            self::CACHE_LIFETIME,
        );
    }

    /**
     * Request onboarding status
     *
     * @param string $environment
     * @return array
     * @throws HttpException
     */
    private function requestStatus(string $environment) : array
    {
        $result = $this->serviceClient->request(
            ['Content-Type' => 'application/json'],
            '/onboarding/paypal',
            Http::METHOD_GET,
            '',
            'json',
            $environment,
        );
        if (!$result['is_successful']) {
            throw new HttpException($result['message'], $result['status']);
        }
        return $result['result'];
    }
}
