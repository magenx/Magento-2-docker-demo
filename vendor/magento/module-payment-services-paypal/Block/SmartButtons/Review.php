<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Block\SmartButtons;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Framework\View\Element\Template\Context;
use Magento\Tax\Helper\Data;
use Magento\Customer\Model\Address\Config;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Framework\Convert\ConvertArray;
use Magento\Customer\Block\Address\Renderer\RendererInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\DataObject;
use Magento\PaymentServicesPaypal\Model\SmartButtons\Checkout;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Review extends \Magento\Framework\View\Element\Template
{
    private const NOT_AVAILABLE_SHIPPING_METHODS = ['instore'];
    private const DEFAULT_PAYMENT_SOURCE = 'paypal';
    /**
     * @var AddressInterface
     */
    private $address;

    /**
     * @var Config
     */
    private $addressConfig;

    /**
     * @var Rate
     */
    private $currentShippingRate;

    /**
     * @var string
     */
    private $controllerPath = 'paymentservicespaypal/smartbuttons';

    /**
     * @var Data
     */
    private $taxHelper;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var Checkout
     */
    private $checkout;

    /**
     * @param Context $context
     * @param Data $taxHelper
     * @param Config $addressConfig
     * @param PriceCurrencyInterface $priceCurrency
     * @param CheckoutSession $checkoutSession
     * @param Checkout $checkout
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $taxHelper,
        Config $addressConfig,
        PriceCurrencyInterface $priceCurrency,
        CheckoutSession $checkoutSession,
        Checkout $checkout,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->taxHelper = $taxHelper;
        $this->addressConfig = $addressConfig;
        $this->checkout = $checkout;
        parent::__construct($context, $data);
        $customerQuoteId = $checkoutSession->getQuoteId();
        $checkoutSession->replaceQuote($checkout->getQuote());
        $checkoutSession->setQuoteId($customerQuoteId);
    }

    /**
     * Get billing address.
     *
     * @return AddressInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getBillingAddress() : AddressInterface
    {
        return $this->checkout->getQuote()->getBillingAddress();
    }

    /**
     * Filter shipping rates not allowed.
     *
     * @param array $groups
     * @return array
     */
    private function filterShippingRates(array $groups): array
    {
        foreach (self::NOT_AVAILABLE_SHIPPING_METHODS as $method) {
            unset($groups[$method]);
        }
        return $groups;
    }

    /**
     * Get shipping address.
     *
     * @return AddressInterface|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getShippingAddress() :? AddressInterface
    {
        if ($this->checkout->getQuote()->getIsVirtual()) {
            return null;
        }
        return $this->checkout->getQuote()->getShippingAddress();
    }

    /**
     * Get HTML output for specified address
     *
     * @param AddressInterface $address
     * @return string
     */
    public function renderAddress(AddressInterface $address) : string
    {
        /** @var RendererInterface $renderer */
        $renderer = $this->addressConfig->getFormatByCode('html')->getRenderer();
        $addressData = ConvertArray::toFlatArray($address->getData());
        return $renderer->renderArray($addressData);
    }

    /**
     * Return carrier name from config, base on carrier code
     *
     * @param string $carrierCode
     * @return string
     */
    public function getCarrierName(string $carrierCode) : string
    {
        $name = $name = $this->_scopeConfig->getValue(
            "carriers/{$carrierCode}/title",
            ScopeInterface::SCOPE_STORE
        );
        if ($name) {
            return $name;
        }
        return $carrierCode;
    }

    /**
     * Get either shipping rate code or empty value on error
     *
     * @param DataObject $rate
     * @return string
     */
    public function renderShippingRateValue(DataObject $rate) : string
    {
        if ($rate->getErrorMessage()) {
            return '';
        }
        return $rate->getCode();
    }

    /**
     * Get shipping rate code title and its price or error message
     *
     * @param DataObject $rate
     * @param string $format
     * @param string $inclTaxFormat
     * @return string
     */
    public function renderShippingRateOption($rate, $format = '%s - %s%s', $inclTaxFormat = ' (%s %s)') : string
    {
        $renderedInclTax = '';
        if ($rate->getErrorMessage()) {
            $price = $rate->getErrorMessage();
        } else {
            $price = $this->getShippingPrice(
                $rate->getPrice(),
                $this->taxHelper->displayShippingPriceIncludingTax()
            );

            $incl = $this->getShippingPrice($rate->getPrice(), true);
            if ($incl != $price && $this->taxHelper->displayShippingBothPrices()) {
                $renderedInclTax = sprintf($inclTaxFormat, $this->_escaper->escapeHtml(__('Incl. Tax')), $incl);
            }
        }
        return sprintf($format, $this->_escaper->escapeHtml($rate->getMethodTitle()), $price, $renderedInclTax);
    }

    /**
     * Getter for current shipping rate
     *
     * @return Rate|null
     */
    public function getCurrentShippingRate() :? Rate
    {
        return $this->currentShippingRate;
    }

    /**
     * Check if shipping method editable.
     *
     * @return bool
     */
    public function canEditShippingMethod() : bool
    {
        return $this->getData('can_edit_shipping_method') || !$this->getCurrentShippingRate();
    }

    /**
     * Get customer email
     *
     * @return string
     */
    public function getEmail() : string
    {
        return $this->getBillingAddress() && $this->getBillingAddress()->getEmail() ?
            $this->getBillingAddress()->getEmail() : '';
    }

    /**
     * Get payment source
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPaymentSource() : string
    {
        $additionalData = $this->checkout->getQuote()->getPayment()->getAdditionalInformation();
        return $additionalData['payment_source'] ?: '';
    }

    /**
     * Get payment icon
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPaymentIcon() : string
    {
        $paymentSource = $this->getPaymentSource();
        $iconUrl = $this->getViewFileUrl('Magento_PaymentServicesPaypal::images/' . $paymentSource . '.png');
        $notFoundUrl = $this->_getNotFoundUrl();
        return $iconUrl !== $notFoundUrl ?
            $iconUrl :
            $this->getViewFileUrl('Magento_PaymentServicesPaypal::images/' . self::DEFAULT_PAYMENT_SOURCE . '.png');
    }

    /**
     * Return formatted shipping price
     *
     * @param float $price
     * @param bool $isInclTax
     * @return string
     */
    private function getShippingPrice($price, $isInclTax) : string
    {
        return $this->formatPrice($this->taxHelper->getShippingPrice($price, $isInclTax, $this->address));
    }

    /**
     * Format price base on store convert price method
     *
     * @param float $price
     * @return string
     */
    private function formatPrice($price) : string
    {
        return $this->priceCurrency->convertAndFormat(
            $price,
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->checkout->getQuote()->getStore()
        );
    }

    /**
     * Assign template values
     *
     * @return Review
     */
    protected function _beforeToHtml() : Review
    {
        $methodInstance = $this->checkout->getQuote()
            ->getPayment()
            ->getMethodInstance();
        $this->setPaymentMethodTitle($methodInstance->getTitle());

        $this->setShippingRateRequired(true);
        $this->setIsQuoteVirtual($this->checkout->getQuote()->getIsVirtual() ? 1 : 0);
        if ($this->checkout->getQuote()->getIsVirtual()) {
            $this->setShippingRateRequired(false);
        } else {
            $this->address = $this->checkout->getQuote()->getShippingAddress();
            $groups = $this->address->getGroupedAllShippingRates();
            $groups = $this->filterShippingRates($groups);
            if ($groups && $this->address) {
                $this->setShippingRateGroups($groups);
                foreach ($groups as $rates) {
                    foreach ($rates as $rate) {
                        if ($this->address->getShippingMethod() == $rate->getCode()) {
                            $this->currentShippingRate = $rate;
                            break 2;
                        }
                    }
                }
            }

            $this->setCanEditShippingAddress(false)
                ->setCanEditShippingMethod(true)
                ->setShippingMethodSubmitUrl(
                    $this->getUrl("{$this->controllerPath}/saveshippingmethod", ['_secure' => true])
                );
        }

        $this->setPlaceOrderUrl($this->getUrl("{$this->controllerPath}/placeorder", ['_secure' => true]));

        return parent::_beforeToHtml();
    }
}
