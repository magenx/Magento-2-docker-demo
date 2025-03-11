<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\InstantPurchase;

use Magento\InstantPurchase\Model\ShippingMethodChoose\CheapestMethodDeferredChooser as VanillaCheapestMethodDeferredChooser;
use Magento\InstantPurchase\Model\ShippingMethodChoose\DeferredShippingMethodChooserInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\PaymentServicesPaypal\Model\HostedFieldsConfigProvider;

/**
 * Chooses the cheapest shipping method (excluding in-store pickup) for the defined quote.
 *
 * Uses previously collected shipping rates instead of computing new ones if available. Falls back to standard
 * 'cheapest' deferred shipping method for instant purchase orders that are not placed with payment services.
 */
class CheapestMethodDeferredChooser implements DeferredShippingMethodChooserInterface
{
    public const METHOD_CODE = 'payment-services-cheapest';

    private PaymentMethodResolver $paymentMethodResolver;
    private VanillaCheapestMethodDeferredChooser $vanillaCheapestMethodDeferredChooser;

    /**
     * @param PaymentMethodResolver $paymentMethodResolver
     * @param VanillaCheapestMethodDeferredChooser $vanillaCheapestMethodDeferredChooser
     */
    public function __construct(
        PaymentMethodResolver                $paymentMethodResolver,
        VanillaCheapestMethodDeferredChooser $vanillaCheapestMethodDeferredChooser,
    )
    {
        $this->paymentMethodResolver = $paymentMethodResolver;
        $this->vanillaCheapestMethodDeferredChooser = $vanillaCheapestMethodDeferredChooser;
    }

    /**
     * @inheritdoc
     */
    public function choose(Address $address)
    {
        if ($this->paymentMethodResolver->resolve() !== HostedFieldsConfigProvider::CODE) {
            // Fall back to standard 'cheapest' deferred shipping method
            return $this->vanillaCheapestMethodDeferredChooser->choose($address);
        }

        if (!empty($shippingRates = $this->getShippingRates($address))) {
            return $this->selectCheapestRate($shippingRates)->getCode();
        }

        return null;
    }

    /**
     * Retrieves previously collected shipping rates for address or computes new ones.
     *
     * @param Address $address
     * @return Rate[]
     */
    private function getShippingRates(Address $address): array
    {

        if (!empty($shippingRates = $address->getAllShippingRates())) {
            // Favour previously collected rates over recomputing.
            return $this->filterOutISPU($shippingRates);
        }

        $address->setCollectShippingRates(true);
        $address->collectShippingRates();
        $shippingRates = $address->getAllShippingRates();
        return $this->filterOutISPU($shippingRates);
    }

    /**
     * Selects shipping price with minimal price.
     *
     * @param Rate[] $shippingRates
     * @return Rate
     */
    private function selectCheapestRate(array $shippingRates): Rate
    {
        $rate = array_shift($shippingRates);
        foreach ($shippingRates as $tmpRate) {
            if ($tmpRate->getPrice() < $rate->getPrice()) {
                $rate = $tmpRate;
            }
        }
        return $rate;
    }

    /**
     * @param array $shippingRates
     * @return array
     */
    private function filterOutISPU(array $shippingRates): array
    {
        /**
         * Filter our in-store pickup, as it is not a valid shipping method for Instant Purchase.
         * Code name comes from Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup::DELIVERY_METHOD
         */
        return array_filter($shippingRates, static function ($rate) {
            return $rate->getCode() !== 'instore_pickup';
        });
    }
}
