<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PaymentServicesPaypal\Model\ApplePayConfigProvider;
use Magento\PaymentServicesPaypal\Model\Config;
use Magento\PaymentServicesPaypal\Model\GooglePayConfigProvider;
use Magento\PaymentServicesPaypal\Model\SdkService;
use Magento\PaymentServicesPaypal\Model\SdkService\PaymentOptionsBuilderFactory;
use Magento\PaymentServicesPaypal\Model\SmartButtonsConfigProvider;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Sdk Params Source
 */
class SdkParams extends DataObject implements SectionSourceInterface
{
    private const LOCATION = 'customer_data';
    private const LOCATION_APPLE_PAY = 'customer_data_apple_pay';
    private const LOCATION_GOOGLE_PAY = 'customer_data_google_pay';

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
    private $sdkService;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param PaymentOptionsBuilderFactory $paymentOptionsBuilderFactory
     * @param SdkService $sdkService
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        PaymentOptionsBuilderFactory $paymentOptionsBuilderFactory,
        SdkService $sdkService,
        StoreManagerInterface $storeManager,
        Config $config,
        array $data = []
    ) {
        parent::__construct($data);
        $this->paymentOptionsBuilderFactory = $paymentOptionsBuilderFactory;
        $this->sdkService = $sdkService;
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function getSectionData()
    {
        return [
            'sdkParams' => [
                'paypal' => $this->getSdkParams(self::LOCATION, SmartButtonsConfigProvider::CODE),
                'applepay' => $this->getSdkParams(self::LOCATION_APPLE_PAY, ApplePayConfigProvider::CODE),
                'googlepay' => $this->getSdkParams(self::LOCATION_GOOGLE_PAY, GooglePayConfigProvider::CODE),
            ]
        ];
    }

    /**
     * Get script params for paypal smart buttons sdk.
     *
     * @param string $location
     * @param string $code
     * @return array
     * @throws NoSuchEntityException
     */
    private function getSdkParams(string $location, string $code): array
    {
        if (!$this->config->isEnabled()) {
            return [];
        }
        $storeViewId = $this->storeManager->getStore()->getId();
        $cachedParams = $this->sdkService->loadFromSdkParamsCache($location, (string)$storeViewId);
        if (count($cachedParams) > 0) {
            return $cachedParams;
        }
        $paymentOptions = [];
        if ($code === SmartButtonsConfigProvider::CODE) {
            $paymentOptions = $this->getSmartButtonsOptions();
        } elseif ($code === ApplePayConfigProvider::CODE) {
            $paymentOptions = $this->getApplePayOptions();
        } elseif ($code === GooglePayConfigProvider::CODE) {
            $paymentOptions = $this->getGooglePayOptions();
        }

        try {
            $params = $this->sdkService->getSdkParams(
                $paymentOptions,
                true,
                $this->config->getPaymentIntent($code)
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
        return $result;
    }

    /**
     * Get script option for paypal smart buttons sdk.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    private function getSmartButtonsOptions(): array
    {
        $paymentOptionsBuilder = $this->paymentOptionsBuilderFactory->create();
        $paymentOptionsBuilder->setAreButtonsEnabled(true);
        $paymentOptionsBuilder->setIsPayPalCreditEnabled($this->config->isFundingSourceEnabledByName('paypal_credit'));
        $paymentOptionsBuilder->setIsVenmoEnabled($this->config->isFundingSourceEnabledByName('venmo'));
        $paymentOptionsBuilder->setIsApplePayEnabled(false);
        $paymentOptionsBuilder->setIsCreditCardEnabled(false);
        $paymentOptionsBuilder->setIsPaylaterMessageEnabled(
            $this->config->canDisplayPayLaterMessage()
        );
        return $paymentOptionsBuilder->build();
    }

    /**
     * Get script option for Apple Pay sdk.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    private function getApplePayOptions(): array
    {
        $paymentOptionsBuilder = $this->paymentOptionsBuilderFactory->create();
        $paymentOptionsBuilder->setAreButtonsEnabled(false);
        $paymentOptionsBuilder->setIsPayPalCreditEnabled(false);
        $paymentOptionsBuilder->setIsVenmoEnabled(false);
        $paymentOptionsBuilder->setIsApplePayEnabled(true);
        $paymentOptionsBuilder->setIsCreditCardEnabled(false);
        $paymentOptionsBuilder->setIsPaylaterMessageEnabled(false);
        return $paymentOptionsBuilder->build();
    }

    /**
     * Get script option for paypal smart buttons sdk.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    private function getGooglePayOptions(): array
    {
        $paymentOptionsBuilder = $this->paymentOptionsBuilderFactory->create();
        $paymentOptionsBuilder->setAreButtonsEnabled(false);
        $paymentOptionsBuilder->setIsPayPalCreditEnabled(false);
        $paymentOptionsBuilder->setIsVenmoEnabled(false);
        $paymentOptionsBuilder->setIsGooglePayEnabled(true);
        $paymentOptionsBuilder->setIsCreditCardEnabled(false);
        $paymentOptionsBuilder->setIsPaylaterMessageEnabled(false);
        return $paymentOptionsBuilder->build();
    }
}
