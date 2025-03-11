<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\PaymentServicesPaypal\Model\SdkService\PaymentOptionsBuilderFactory;
use Magento\PaymentServicesPaypal\Model\SdkService\PaymentOptionsBuilder;
use Magento\Framework\UrlInterface;
use Magento\PaymentServicesBase\Model\Config as BaseConfig;

class GooglePayConfigProvider implements ConfigProviderInterface
{
    public const CODE = Config::PAYMENTS_SERVICES_PREFIX . 'google_pay';

    private const LOCATION = 'checkout_googlepay';

    public const PAYMENT_SOURCE = 'googlepay';

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var BaseConfig
     */
    private $baseConfig;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @param Config $config
     * @param UrlInterface $url
     * @param BaseConfig $baseConfig
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        Config $config,
        UrlInterface $url,
        BaseConfig $baseConfig,
        ConfigProvider $configProvider
    ) {
        $this->baseConfig = $baseConfig;
        $this->config = $config;
        $this->url = $url;
        $this->configProvider = $configProvider;
    }

    /**
     * @var UrlInterface
     */
    private UrlInterface $url;

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        $config = $this->configProvider->getConfig();
        if (!$this->baseConfig->isConfigured() || !$this->config->isGooglePayLocationEnabled('checkout')) {
            $config['payment'][self::CODE]['isVisible'] = false;
            return $config;
        }

        $config['payment'][self::CODE]['mode'] = $this->config->getGooglePayMode();
        $config['payment'][self::CODE]['isVisible'] = true;
        $config['payment'][self::CODE]['createOrderUrl'] = $this->url->getUrl('paymentservicespaypal/order/create');
        $config['payment'][self::CODE]['getOrderDetailsUrl'] = $this->url->getUrl('paymentservicespaypal/order/getcurrentorder');
        $config['payment'][self::CODE]['threeDSMode'] = $this->config->getGooglePayThreeDS() !== "0" ? $this->config->getGooglePayThreeDS() : false;
        $config['payment'][self::CODE]['sdkParams'] = $this->configProvider->getScriptParams(
            self::CODE,
            self::LOCATION,
            $this->getPaymentOptions()
        );
        $config['payment'][self::CODE]['styles'] =
            array_merge($this->config->getButtonConfiguration(), $this->getGooglePayStyles());
        $config['payment'][self::CODE]['paymentSource'] = self::PAYMENT_SOURCE;
        $config['payment'][self::CODE]['paymentTypeIconUrl'] =
            $this->config->getViewFileUrl('Magento_PaymentServicesPaypal::images/googlepay.png');

        return $config;
    }

    /**
     * Get Google Pay styles
     *
     * @return array
     */
    private function getGooglePayStyles() : array
    {
        return $this->config->getGooglePayStyles();
    }

    /**
     * @inheritdoc
     */
    private function getPaymentOptions(): PaymentOptionsBuilder
    {
        $paymentOptionsBuilder = $this->configProvider->getPaymentOptions();
        $paymentOptionsBuilder->setAreButtonsEnabled(false);
        $paymentOptionsBuilder->setIsPayPalCreditEnabled(false);
        $paymentOptionsBuilder->setIsVenmoEnabled(false);
        $paymentOptionsBuilder->setIsGooglePayEnabled($this->config->isGooglePayLocationEnabled('checkout'));
        $paymentOptionsBuilder->setIsCreditCardEnabled(false);
        $paymentOptionsBuilder->setIsPaylaterMessageEnabled(false);
        return $paymentOptionsBuilder;
    }
}
