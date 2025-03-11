<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Adminhtml;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\PaymentServicesPaypal\Model\SdkService;
use Magento\PaymentServicesPaypal\Model\SdkService\PaymentOptionsBuilderFactory;
use Magento\PaymentServicesPaypal\Model\Config;
use Magento\PaymentServicesPaypal\Model\HostedFieldsConfigProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

use Magento\Framework\DataObject;

class SdkParams extends DataObject
{
    private const LOCATION = 'admin_checkout';

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
     * @var Config
     */
    private $config;

    /**
     * SdkParams constructor.
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
     * Get sdk params.
     *
     * @param string|null $websiteId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getSdkParams($websiteId = null): array
    {
        $storeViewId = $this->storeManager->getStore()->getId();
        $cachedParams = $this->sdkService->loadFromSdkParamsCache(self::LOCATION, (string)$storeViewId);
        if (count($cachedParams) > 0) {
            return $cachedParams;
        }
        $paymentOptionsBuilder = $this->paymentOptionsBuilderFactory->create();
        $paymentOptionsBuilder->setAreButtonsEnabled(false);
        $paymentOptionsBuilder->setIsPayPalCreditEnabled($this->config->isFundingSourceEnabledByName('paypal_credit'));
        $paymentOptionsBuilder->setIsVenmoEnabled($this->config->isFundingSourceEnabledByName('venmo'));
        $paymentOptionsBuilder->setIsCreditCardEnabled(true);
        $paymentOptionsBuilder->useHostedFieldsForCreditCard();
        $paymentOptionsBuilder->setIsPaylaterMessageEnabled(false);
        $paymentOptions = $paymentOptionsBuilder->build();
        try {
            $paymentIntent = $this->config->getPaymentIntent(HostedFieldsConfigProvider::CODE);
            $params = $this->sdkService->getSdkParams(
                $paymentOptions,
                false,
                $paymentIntent,
                $websiteId
            );
        } catch (InvalidArgumentException $e) {
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
            $this->sdkService->updateSdkParamsCache($result, self::LOCATION, (string)$storeViewId);
        }
        return $result;
    }
}
