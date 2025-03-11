<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\PaymentServicesPaypal\Block;

use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template\Context;
use Magento\PaymentServicesPaypal\Model\Config;
use Magento\Catalog\Block\ShortcutInterface;
use Magento\Framework\View\Element\Template;

/**
 * @api
 */
class SmartButtons extends Template implements ShortcutInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $componentConfig;

    /**
     * @var string
     */
    private $pageType;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var CompositeConfigProvider
     */
    protected $configProvider;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Context $context
     * @param Config $config
     * @param Session $session
     * @param string $pageType
     * @param array $componentConfig
     * @param array $data
     * @param Json|null $serializer
     * @param CompositeConfigProvider|null $compositeConfigProvider
     */
    public function __construct(
        Context $context,
        Config $config,
        Session $session,
        string $pageType = 'minicart',
        array $componentConfig = [],
        array $data = [],
        ?Json $serializer = null,
        ?CompositeConfigProvider $compositeConfigProvider = null
    ) {
        $this->config = $config;
        $this->componentConfig = $componentConfig;
        $this->pageType = $pageType;
        $this->session = $session;
        parent::__construct(
            $context,
            $data
        );
        /** @phpstan-ignore-next-line */
        $this->setTemplate($data['template'] ?? $componentConfig[$this->pageType]['template']);
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        $this->configProvider = $compositeConfigProvider ?: ObjectManager::getInstance()->get(CompositeConfigProvider::class);
    }

    /**
     * Get payment method alias
     *
     * @return string
     */
    public function getAlias() : string
    {
        return 'magpaypayments_smart_buttons';
    }

    /**
     * Get component params of payment methods
     *
     * @return array
     */
    public function getComponentParams() : array
    {
        return [
            'createOrderUrl' => $this->getUrl('paymentservicespaypal/smartbuttons/createpaypalorder'),
            'authorizeOrderUrl' => $this->getUrl('paymentservicespaypal/smartbuttons/updatequote'),
            'orderReviewUrl' => $this->getUrl('paymentservicespaypal/smartbuttons/review'),
            'cancelUrl' => $this->getUrl('checkout/cart'),
            'estimateShippingMethodsWhenLoggedInUrl' => $this->getUrl('rest/V1/carts/mine/estimate-shipping-methods'),
            'estimateShippingMethodsWhenGuestUrl' => $this->getUrl('rest/V1/guest-carts/:cartId/estimate-shipping-methods'),
            'shippingInformationWhenLoggedInUrl' => $this->getUrl('rest/V1/carts/mine/shipping-information'),
            'shippingInformationWhenGuestUrl' => $this->getUrl('rest/V1/guest-carts/:quoteId/shipping-information'),
            'updatePayPalOrderUrl' => $this->getUrl('paymentservicespaypal/smartbuttons/updatepaypalorder/'),
            'countriesUrl' => $this->getUrl('rest/V1/directory/countries/:countryCode'),
            'setQuoteAsInactiveUrl' => $this->getUrl('paymentservicespaypal/smartbuttons/setquoteasinactive'),
            'placeOrderUrl' => $this->getUrl('paymentservicespaypal/smartbuttons/placeorder/'),
            'getOrderDetailsUrl' => $this->getUrl('paymentservicespaypal/order/getcurrentorder'),
            'threeDSMode' => $this->config->getGooglePayThreeDS() !== "0" ? $this->config->getGooglePayThreeDS() : false,
            'styles' => $this->getStyles(),
            'isVirtual' => $this->session->getQuote()->isVirtual(),
            'googlePayMode' => $this->config->getGooglePayMode(),
            'pageType' => $this->pageType,
        ];
    }

    /**
     * Check if smart buttons enabled.
     *
     * @return bool
     */
    public function isEnabled() : bool
    {
        return $this->config->isEnabled();
    }

    /**
     * Check if smart buttons for a particular location (e.g., minicart) is enabled
     *
     * @param string $location
     * @return bool
     */
    public function isLocationEnabled(string $location): bool
    {
        return $this->config->isLocationEnabled($location) && $this->isEnabled();
    }

    /**
     * Check if Apple Pay for a particular location (e.g., minicart) is enabled
     *
     * @param string $location
     * @return bool
     */
    public function isApplePayLocationEnabled(string $location): bool
    {
        return $this->config->isApplePayLocationEnabled($location) && $this->isEnabled();
    }

    /**
     * Check if Google Pay for a particular location (e.g., minicart) is enabled
     *
     * @param string $location
     * @return bool
     */
    public function isGooglePayLocationEnabled(string $location): bool
    {
        return $this->config->isGooglePayLocationEnabled($location) && $this->isEnabled();
    }

    /**
     * Get styles of Smart Buttons
     *
     * @return array
     */
    private function getStyles() : array
    {
        return array_merge($this->config->getButtonConfiguration(), $this->config->getGooglePayStyles());
    }

    /**
     * Get Serialized Checkout Config
     *
     * @return bool|string
     */
    public function getSerializedCheckoutConfig()
    {
        return $this->serializer->serialize($this->configProvider->getConfig());
    }
}
