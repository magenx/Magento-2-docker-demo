<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Adapter\PaymentMethod;

use Braintree\CreditCard;
use Braintree\PayPalAccount;
use Magento\Framework\Exception\InvalidArgumentException;
use PayPal\Braintree\Model\Ui\ConfigProvider as BraintreeConfigProvider;
use PayPal\Braintree\Model\Ui\PayPal\ConfigProvider as BraintreePayPalConfigProvider;

class PaymentTokenAdapterFactory implements PaymentTokenAdapterFactoryInterface
{
    /**
     * @var BraintreePaymentTokenAdapterFactory
     */
    private BraintreePaymentTokenAdapterFactory $braintreePaymentTokenAdapterFactory;

    /**
     * @var BraintreePayPalPaymentTokenAdapterFactory
     */
    private BraintreePayPalPaymentTokenAdapterFactory $braintreePayPalPaymentTokenAdapterFactory;

    /**
     * @param BraintreePaymentTokenAdapterFactory $braintreePaymentTokenAdapterFactory
     * @param BraintreePayPalPaymentTokenAdapterFactory $braintreePayPalPaymentTokenAdapterFactory
     */
    public function __construct(
        BraintreePaymentTokenAdapterFactory $braintreePaymentTokenAdapterFactory,
        BraintreePayPalPaymentTokenAdapterFactory $braintreePayPalPaymentTokenAdapterFactory
    ) {
        $this->braintreePaymentTokenAdapterFactory = $braintreePaymentTokenAdapterFactory;
        $this->braintreePayPalPaymentTokenAdapterFactory = $braintreePayPalPaymentTokenAdapterFactory;
    }

    /**
     * Create payment token adapter
     *
     * @param string $paymentMethodCode
     * @param CreditCard|PayPalAccount $paymentMethod
     * @return PaymentTokenAdapterInterface
     * @throws InvalidArgumentException
     */
    public function create(
        string $paymentMethodCode,
        CreditCard|PayPalAccount $paymentMethod
    ): PaymentTokenAdapterInterface {
        return match ($paymentMethodCode) {
            BraintreeConfigProvider::CODE => $this->braintreePaymentTokenAdapterFactory->create([
                'paymentMethod' => $paymentMethod
            ]),
            BraintreePayPalConfigProvider::PAYPAL_CODE => $this->braintreePayPalPaymentTokenAdapterFactory->create([
                'paymentMethod' => $paymentMethod
            ]),
            default => throw new InvalidArgumentException(
                __('There is no available Payment Token Adapter for %1', $paymentMethodCode)
            )
        };
    }
}
