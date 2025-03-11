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

namespace Magento\PaymentServicesPaypal\Plugin\InstantPurchase;

use Magento\InstantPurchase\Model\ShippingMethodChoose\CheapestMethodChooser as InstantPurchaseCheapestMethodChooser;
use Magento\InstantPurchase\Model\ShippingMethodChoose\CheapestMethodDeferredChooser as VanillaCheapestMethodDeferredChooser;
use Magento\InstantPurchase\Model\ShippingMethodChoose\DeferredShippingMethodChooserInterface;
use Magento\PaymentServicesPaypal\Model\InstantPurchase\CheapestMethodDeferredChooser;
use Magento\Quote\Api\Data\ShippingMethodInterface;


class CheapestMethodChooser
{
    public function afterChoose(
        InstantPurchaseCheapestMethodChooser $subject,
        ShippingMethodInterface|null $shippingMethod,
    )
    {
        if ($shippingMethod === null) {
            return null;
        }

        if ($shippingMethod->getCarrierCode() === DeferredShippingMethodChooserInterface::CARRIER
            && $shippingMethod->getMethodCode() === VanillaCheapestMethodDeferredChooser::METHOD_CODE)
        {
            // Fixes https://github.com/magento/magento2/issues/38811
            $methodCode = CheapestMethodDeferredChooser::METHOD_CODE;
            $shippingMethod->setMethodCode($methodCode);
        }

        return $shippingMethod;
    }
}
