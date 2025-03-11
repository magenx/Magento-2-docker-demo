<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PaymentServicesPaypal\Model\SdkService\PaymentOptionsBuilder;
use Magento\PaymentServicesPaypal\Model\SdkService\PaymentOptionsBuilderFactory;
use Magento\Store\Model\StoreManagerInterface;

class ConfigProvider
{
    public const CODE = '';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var PaymentOptionsBuilderFactory
     */
    private $paymentOptionsBuilderFactory;

    /**
     * @var SdkService
     */
    private SdkService $sdkService;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var mixed
     */
    private mixed $cspNonceProvider;

    /**
     * @param Config $config
     * @param PaymentOptionsBuilderFactory $paymentOptionsBuilderFactory
     * @param SdkService $sdkService
     * @param StoreManagerInterface $storeManager
     *
     */
    public function __construct(
        Config $config,
        PaymentOptionsBuilderFactory $paymentOptionsBuilderFactory,
        SdkService $sdkService,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->paymentOptionsBuilderFactory = $paymentOptionsBuilderFactory;
        $this->sdkService = $sdkService;
        $this->storeManager = $storeManager;
        //TODO:Just to be compatible with 2.4.6. Remove in future
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        try {
            $this->cspNonceProvider = $objectManager->get("\Magento\Csp\Helper\CspNonceProvider");
        } catch (\Throwable $e) {
            $this->cspNonceProvider = null;
        }
    }

    /**
     * Get default config.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                $this->getCode() => []
            ]
        ];
    }

    /**
     * Get payment method code.
     *
     * @return string
     */
    private function getCode() : string
    {
        return self::CODE;
    }

    /**
     * Get script params for PayPal js sdk loading.
     *
     * @param string $paymentCode
     * @param string $location
     * @param PaymentOptionsBuilder $paymentOptionsBuilder
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD)
     */
    public function getScriptParams(
        string $paymentCode,
        string $location,
        PaymentOptionsBuilder $paymentOptionsBuilder
    ) : array {
        $storeViewId = $this->storeManager->getStore()->getId();
        $cachedParam = $this->sdkService->loadFromSdkParamsCache($location, (string)$storeViewId);
        if ($this->cspNonceProvider !== null) {
            $cspNonceParam = ['name' => 'data-csp-nonce', 'value' => $this->cspNonceProvider->generateNonce()];
        } else {
            $cspNonceParam = [];
        }
        if (count($cachedParam) > 0) {
            array_push($cachedParam, $cspNonceParam);
            return $cachedParam;
        }
        $paymentOptions = $paymentOptionsBuilder->build();
        try {
            $paymentIntent = $this->config->getPaymentIntent($paymentCode);
            $params = $this->sdkService->getSdkParams(
                $paymentOptions,
                false,
                $paymentIntent
            );
        } catch (\InvalidArgumentException | NoSuchEntityException $e) {
            return [];
        }
        $result = [];

        foreach ($params as $param) {
            $result[] = [
                'name' => $param['name'],
                'value' => $param['value']
            ];
        }
        if (count($result) > 0) {
            $this->sdkService->updateSdkParamsCache($result, $location, (string)$storeViewId);
        }
        array_push($result, $cspNonceParam);
        return $result;
    }

    /**
     * Get payment options.
     *
     * @return PaymentOptionsBuilder
     */
    public function getPaymentOptions(): PaymentOptionsBuilder
    {
        return $this->paymentOptionsBuilderFactory->create();
    }
}
