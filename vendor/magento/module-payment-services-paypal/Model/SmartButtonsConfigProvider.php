<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\PaymentServicesPaypal\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\PaymentServicesPaypal\Model\SdkService\PaymentOptionsBuilder;
use Magento\PaymentServicesPaypal\Model\SdkService\PaymentOptionsBuilderFactory;
use Magento\Framework\UrlInterface;
use Magento\PaymentServicesBase\Model\Config as BaseConfig;

class SmartButtonsConfigProvider implements ConfigProviderInterface
{
    public const CODE = Config::PAYMENTS_SERVICES_PREFIX . 'smart_buttons';

    private const LOCATION = 'checkout_smart_buttons';

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var BaseConfig
     */
    private BaseConfig $baseConfig;

    /**
     * @var array
     */
    private array $messageStyles;

    /**
     * @var UrlInterface
     */
    private UrlInterface $url;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @param Config $config
     * @param UrlInterface $url
     * @param BaseConfig $baseConfig
     * @param array $messageStyles
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        Config $config,
        UrlInterface $url,
        BaseConfig $baseConfig,
        array $messageStyles,
        ConfigProvider $configProvider
    ) {
        $this->baseConfig = $baseConfig;
        $this->config = $config;
        $this->url = $url;
        $this->messageStyles = $messageStyles;
        $this->configProvider = $configProvider;
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        $config = $this->configProvider->getConfig();
        if (!$this->baseConfig->isConfigured() || !$this->config->isLocationEnabled('checkout')) {
            $config['payment'][self::CODE]['isVisible'] = false;
            return $config;
        }
        $config['payment'][self::CODE]['isVisible'] = true;
        $config['payment'][self::CODE]['createOrderUrl'] = $this->url->getUrl('paymentservicespaypal/order/create');
        $config['payment'][self::CODE]['sdkParams'] = $this->configProvider->getScriptParams(
            self::CODE,
            self::LOCATION,
            $this->getPaymentOptions()
        );
        $config['payment'][self::CODE]['messageStyles'] = $this->messageStyles;
        $config['payment'][self::CODE]['canDisplayMessage'] = (bool) $this->config->canDisplayPayLaterMessage();
        $config['payment'][self::CODE]['buttonStyles'] = $this->config->getButtonConfiguration();
        $config['payment'][self::CODE]['paymentTypeIconUrl'] =
            $this->config->getViewFileUrl('Magento_PaymentServicesPaypal::images/paypal_vertical.png');
        return $config;
    }

    /**
     * @inheritdoc
     */
    private function getPaymentOptions(): PaymentOptionsBuilder
    {
        $paymentOptionsBuilder = $this->configProvider->getPaymentOptions();
        $paymentOptionsBuilder->setAreButtonsEnabled($this->config->isLocationEnabled('checkout'));
        $paymentOptionsBuilder->setIsPayPalCreditEnabled($this->config->isFundingSourceEnabledByName('paypal_credit'));
        $paymentOptionsBuilder->setIsVenmoEnabled($this->config->isFundingSourceEnabledByName('venmo'));
        $paymentOptionsBuilder->setIsApplePayEnabled(false);
        $paymentOptionsBuilder->setIsPayPalCardEnabled($this->config->isFundingSourceEnabledByName('card'));
        $paymentOptionsBuilder->setIsPaylaterMessageEnabled($this->config->canDisplayPayLaterMessage());
        return $paymentOptionsBuilder;
    }
}
