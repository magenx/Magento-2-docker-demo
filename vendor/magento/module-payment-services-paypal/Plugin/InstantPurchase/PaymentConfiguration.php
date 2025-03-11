<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Plugin\InstantPurchase;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\PaymentServicesBase\Model\HttpException;
use Magento\PaymentServicesPaypal\Helper\OrderHelper;
use Magento\PaymentServicesPaypal\Model\OrderService;
use Magento\PaymentServicesPaypal\Model\Ui\TokenUiComponentProvider;
use Magento\Quote\Model\Quote;
use Magento\InstantPurchase\Model\QuoteManagement\PaymentConfiguration as InstantPurchasePaymentConfiguration;
use Magento\Framework\Exception\LocalizedException;
use Magento\PaymentServicesBase\Model\Config;
use Magento\PaymentServicesPaypal\Model\HostedFieldsConfigProvider;

class PaymentConfiguration
{
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @param OrderService $orderService
     * @param CustomerSession $customerSession
     * @param Config $config
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        OrderService $orderService,
        CustomerSession $customerSession,
        Config $config,
        OrderHelper $orderHelper
    ) {
        $this->orderService = $orderService;
        $this->customerSession = $customerSession;
        $this->config = $config;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Create PayPal order on instant purchase.
     *
     * @param InstantPurchasePaymentConfiguration $subject
     * @param Quote $quote
     * @return Quote
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterConfigurePayment(
        InstantPurchasePaymentConfiguration $subject,
        Quote $quote
    ): Quote {
        if ($quote->getPayment()->getMethod() !== HostedFieldsConfigProvider::CC_VAULT_CODE) {
            return $quote;
        }

        $totalAmount = $quote->getBaseGrandTotal();
        $currencyCode = $quote->getCurrency()->getBaseCurrencyCode();
        $customer = $this->customerSession->getCustomer();
        $orderIncrementId = $this->orderHelper->reserveAndGetOrderIncrementId($quote);

        $response = $this->orderService->create(
            [
                'amount' => $this->orderHelper->formatAmount((float)$totalAmount),
                'l2_data' => $this->orderHelper->getL2Data($quote, TokenUiComponentProvider::CC_VAULT_SOURCE),
                'l3_data' => $this->orderHelper->getL3Data($quote, TokenUiComponentProvider::CC_VAULT_SOURCE),
                'currency_code' => $currencyCode,
                'is_digital' => $quote->getIsVirtual(),
                'website_id' => $quote->getStore()->getWebsiteId(),
                'shipping_address' => $this->orderService->mapAddress($quote->getShippingAddress()),
                'billing_address' => $this->orderService->mapAddress($quote->getBillingAddress()),
                'payer' => $this->orderService->buildPayer($quote, $customer->getId()),
                'payment_source' => TokenUiComponentProvider::CC_VAULT_SOURCE,
                'quote_id' => $quote->getId(),
                'order_increment_id' => $orderIncrementId,
                'line_items' => $this->orderHelper->getLineItems($quote, $orderIncrementId),
                'amount_breakdown' => $this->orderHelper->getAmountBreakdown($quote, $orderIncrementId),
            ]
        );
        if (!$response['is_successful']) {
            throw new HttpException('Failed to create an order.');
        }

        $quote->getPayment()
            ->setAdditionalInformation('paypal_order_id', $response['paypal-order']['id'])
            ->setAdditionalInformation('payments_order_id', $response['paypal-order']['mp_order_id'])
            ->setAdditionalInformation('payments_mode', $this->config->getEnvironmentType())
            ->setAdditionalInformation('paypal_order_amount', $totalAmount);

        return $quote;
    }
}
