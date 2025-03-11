<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\SmartButtons;

use Magento\PaymentServicesPaypal\Helper\OrderHelper;
use Magento\PaymentServicesPaypal\Model\ApplePayConfigProvider;
use Magento\PaymentServicesPaypal\Model\GooglePayConfigProvider;
use Magento\PaymentServicesPaypal\Model\OrderService;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote as QuoteEntity;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Checkout\Helper\Data;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Session\Generic as PaypalSession;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Checkout\Model\Type\Onepage;
use Magento\PaymentServicesPaypal\Model\SmartButtonsConfigProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PaymentServicesBase\Model\HttpException;
use Magento\PaymentServicesBase\Model\Config;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Checkout
{
    public const LOCATION_PRODUCT_PAGE = 'product';

    private const SUCCESS_PAGE_URI = 'checkout/onepage/success';

    private const SUCCESS_PAGE_PRODUCT_PAGE_CHECKOUT_URI = 'paymentservicespaypal/smartbuttons/success';

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CartManagementInterface
     */
    private $quoteManagement;

    /**
     * @var Data
     */
    private $checkoutData;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var PaypalSession
     */
    private $paypalSession;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CartInterface
     */
    private $quote;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Data
     */
    private $checkoutHelper;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param CartManagementInterface $quoteManagement
     * @param Data $checkoutData
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param PaypalSession $paypalSession
     * @param OrderSender $orderSender
     * @param OrderService $orderService
     * @param LoggerInterface $logger
     * @param Config $config
     * @param Data $checkoutHelper
     * @param OrderHelper $orderHelper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        CartManagementInterface $quoteManagement,
        Data $checkoutData,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        PaypalSession $paypalSession,
        OrderSender $orderSender,
        OrderService $orderService,
        LoggerInterface $logger,
        Config $config,
        Data $checkoutHelper,
        OrderHelper $orderHelper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->checkoutData = $checkoutData;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->paypalSession = $paypalSession;
        $this->quoteManagement = $quoteManagement;
        $this->orderSender = $orderSender;
        $this->orderService = $orderService;
        $this->logger = $logger;
        $this->config = $config;
        $this->checkoutHelper = $checkoutHelper;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Update quote function
     *
     * @param array $shippingAddress
     * @param array $billingAddress
     * @param string $orderId
     * @param string $payerId
     * @param string $paymentsOrderId
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function updateQuote(
        array $shippingAddress,
        array $billingAddress,
        string $orderId,
        string $payerId,
        string $paymentsOrderId
    ) : void {
        $this->getQuote()
            ->getShippingAddress()
            ->addData($shippingAddress)
            ->setCollectShippingRates(true);
        $this->getQuote()
            ->getBillingAddress()
            ->addData($billingAddress);
        $this->getQuote()
            ->getPayment()
            ->setAdditionalInformation('paypal_payer_id', $payerId)
            ->setAdditionalInformation('paypal_order_id', $orderId)
            ->setAdditionalInformation('payments_order_id', $paymentsOrderId)
            ->setAdditionalInformation('payments_mode', $this->config->getEnvironmentType());
        $this->getQuote()->collectTotals();
        $this->quoteRepository->save($this->getQuote());
    }

    /**
     * Create an order in paypal
     *
     * @param String $paymentSource
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createPayPalOrder(string $paymentSource = '', ?string $threeDSMode = null) : array
    {
        $quote = $this->getQuote();
        $quote->reserveOrderId();
        $quote->getPayment()->setAdditionalInformation('payment_source', $paymentSource);
        $paymentMethod = SmartButtonsConfigProvider::CODE;
        if ($paymentSource === ApplePayConfigProvider::PAYMENT_SOURCE) {
            $paymentMethod = ApplePayConfigProvider::CODE;
        } elseif ($paymentSource === GooglePayConfigProvider::PAYMENT_SOURCE) {
            $paymentMethod = GooglePayConfigProvider::CODE;
        }
        $quote->getPayment()->setMethod($paymentMethod);
        $this->quoteRepository->save($quote);
        $totalAmount = $quote->getBaseGrandTotal();
        $currencyCode = $quote->getCurrency()->getBaseCurrencyCode();
        $quoteId = $quote->getId();
        $saasResponse = $this->orderService->create(
            [
                'amount' => $this->orderHelper->formatAmount((float)$totalAmount),
                'currency_code' => $currencyCode,
                'is_digital' => $quote->getIsVirtual(),
                'website_id' => $quote->getStore()->getWebsiteId(),
                'payment_source' => $paymentSource,
                'three_ds_mode' => $threeDSMode ?: null,
                'quote_id' => $quoteId,
                'order_increment_id' => $this->orderHelper->reserveAndGetOrderIncrementId($quote),
                'line_items' => $this->orderHelper->getLineItems($quote, $quote->getReservedOrderId()),
                'amount_breakdown' => $this->orderHelper->getAmountBreakdown($quote, $quote->getReservedOrderId()),
            ]
        );

        if (isset($saasResponse['is_successful'])
            && $saasResponse['is_successful'] === true
            && isset($saasResponse["paypal-order"]['id'])
        ) {
            $quote->getPayment()->setAdditionalInformation('paypal_order_id', $saasResponse["paypal-order"]['id']);
            $this->quoteRepository->save($quote);
        }

        return array_merge_recursive(
            $saasResponse,
            [
                "paypal-order" => [
                    "amount" => $totalAmount,
                    "currency_code" => $currencyCode
                ],
            ],
        );
    }

    /**
     * Set shipping method for quote
     *
     * @param string $methodCode
     */
    public function updateShippingMethod($methodCode) : void
    {
        $shippingAddress = $this->getQuote()->getShippingAddress();
        if (!$this->getQuote()->getIsVirtual() && $shippingAddress) {
            if ($methodCode != $shippingAddress->getShippingMethod()) {
                $this->ignoreAddressValidation();
                $shippingAddress->setShippingMethod($methodCode)
                    ->setCollectShippingRates(true);
                $cartExtensionAttributes = $this->getQuote()->getExtensionAttributes();
                if ($cartExtensionAttributes->getShippingAssignments()) {
                    $cartExtensionAttributes->getShippingAssignments()[0]
                        ->getShipping()
                        ->setMethod($methodCode);
                }
                $this->getQuote()->collectTotals();
                $this->quoteRepository->save($this->getQuote());
            }
        }
    }

    /**
     * Place an order
     *
     * @return OrderInterface|null
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\SessionException
     */
    public function placeOrder() :? OrderInterface
    {
        if ($this->getCheckoutMethod() == Onepage::METHOD_GUEST) {
            $this->prepareGuestQuote();
        }
        $this->ignoreAddressValidation();
        $this->getQuote()->collectTotals();
        $this->updatePayPalOrder();
        $this->updateQuoteCustomerData($this->getQuote());
        $order = $this->quoteManagement->submit($this->getQuote());
        if (!$order) {
            return null;
        }
        try {
            if (!$order->getEmailSent()) {
                $this->orderSender->send($order);
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        }
        $this->checkoutSession->start();
        $this->checkoutSession->clearHelperData();
        $quoteId = $this->getQuote()->getId();
        $this->checkoutSession->setLastQuoteId($quoteId)
            ->setLastSuccessQuoteId($quoteId);
        if ($order) {
            $this->checkoutSession->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());
        }
        if ($this->paypalSession->getCustomerQuoteId()) {
            $quote = $this->quoteRepository->get($this->paypalSession->getCustomerQuoteId());
            if ($quote->getId()) {
                $quote->setIsActive(true);
                $this->quoteRepository->save($quote);
                $this->checkoutSession->setQuoteId($this->paypalSession->getCustomerQuoteId());
                $this->paypalSession->unsCustomerQuoteId();
                $this->paypalSession->unsQuoteId();
            }
        }
        return $order;
    }

    /**
     * Get quote method
     *
     * @return CartInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote() : CartInterface
    {
        if (!$this->quote) {
            if ($this->paypalSession->getQuoteId()) {
                $this->quote = $this->quoteRepository->getActive($this->paypalSession->getQuoteId());
            } else {
                $this->quote = $this->checkoutSession->getQuote();
            }
        }
        return $this->quote;
    }

    /**
     * Unset quote method
     *
     * @return void
     */
    public function unsetQuote() : void
    {
        $this->paypalSession->unsCustomerQuoteId();
        $this->paypalSession->unsQuoteId();
    }

    /**
     * Validate quote method
     *
     * @throws LocalizedException
     */
    public function validateQuote() : void
    {
        try {
            $quote = $this->getQuote();
        } catch (LocalizedException | NoSuchEntityException $e) {
            throw new LocalizedException(__('Can\'t initialize checkout. Please try again.'));
        }
        if (!$quote->hasItems() || $quote->getHasError()) {
            throw new LocalizedException(__('Can\'t initialize checkout. Please try again.'));
        }
        if (!$this->customerSession->getCustomerId() &&
            !$this->checkoutHelper->isAllowedGuestCheckout($this->checkoutSession->getQuote())
        ) {
            throw new LocalizedException(__('To check out, please sign in with your email address.'));
        }
        if (!(float) $quote->getGrandTotal()) {
            throw new LocalizedException(
                __(
                    'Payment Services can\'t process orders with a zero balance due. '
                    . 'To finish your purchase, please go through the standard checkout process.'
                )
            );
        }
    }

    /**
     *  Set PayPpal session location
     *
     * @param string $location
     */
    public function setLocation($location) : void
    {
        $this->paypalSession->setLocation($location);
    }

    /**
     * Get PayPal Session location
     *
     * @return string $location
     */
    public function getLocation() : string
    {
        return $this->paypalSession->getLocation() ?? '';
    }

    /**
     * Get successul page uri
     *
     * @return string
     */
    public function getSuccessPageUri() : string
    {
        if ($this->paypalSession->getLocation() === self::LOCATION_PRODUCT_PAGE) {
            return self::SUCCESS_PAGE_PRODUCT_PAGE_CHECKOUT_URI;
        }
        return self::SUCCESS_PAGE_URI;
    }

    /**
     * Get checkout method
     *
     * @return string
     */
    private function getCheckoutMethod() : string
    {
        if ($this->customerSession->isLoggedIn()) {
            return Onepage::METHOD_CUSTOMER;
        }
        if (!$this->getQuote()->getCheckoutMethod()) {
            if ($this->checkoutData->isAllowedGuestCheckout($this->getQuote())) {
                $this->getQuote()->setCheckoutMethod(Onepage::METHOD_GUEST);
            } else {
                $this->getQuote()->setCheckoutMethod(Onepage::METHOD_REGISTER);
            }
        }
        return $this->getQuote()->getCheckoutMethod();
    }

    /**
     * Prepare quote for guest checkout
     */
    private function prepareGuestQuote() : void
    {
        $this->getQuote()
            ->setCustomerId(null)
            ->setCustomerEmail($this->getQuote()->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
    }

    /**
     * Disable addresses validation
     */
    private function ignoreAddressValidation() : void
    {
        $this->getQuote()
            ->getBillingAddress()
            ->setShouldIgnoreValidation(true);
        if (!$this->getQuote()->getIsVirtual()) {
            $this->getQuote()
                ->getShippingAddress()
                ->setShouldIgnoreValidation(true);
            if (!$this->getQuote()->getBillingAddress()->getEmail()) {
                $this->getQuote()
                    ->getBillingAddress()
                    ->setSameAsBilling(1);
            }
        }
    }

    /**
     * Upddate PayPal order with amount and currency info
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function updatePayPalOrder() : void
    {
        $quote = $this->getQuote();

        $orderIncrementId = $this->orderHelper->reserveAndGetOrderIncrementId($quote);
        $paypalOrderId = $quote->getPayment()->getAdditionalInformation('paypal_order_id');
        $totalAmount = $quote->getBaseGrandTotal();
        $currencyCode = $quote->getCurrency()->getBaseCurrencyCode();

        try {
            $this->orderService->update(
                (string) $paypalOrderId,
                [
                    'amount' => $this->orderHelper->formatAmount((float)$totalAmount),
                    'currency_code' => $currencyCode,
                    'line_items' => $this->orderHelper->getLineItems($quote, $orderIncrementId),
                    'amount_breakdown' => $this->orderHelper->getAmountBreakdown($quote, $orderIncrementId),
                ]
            );
        } catch (HttpException $e) {
            throw new LocalizedException(__('Your payment was not successful. Try again.'));
        }
    }

    /**
     * Update Quote with customer data from active session
     *
     * @param QuoteEntity $quote
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function updateQuoteCustomerData(QuoteEntity $quote): void {
        if ($this->customerSession->isLoggedIn()) {
            $customerData = $this->customerSession->getCustomerData();
            $quote->setCustomerFirstname($customerData->getFirstname());
            $quote->setCustomerLastname($customerData->getLastname());
            $this->quoteRepository->save($quote);
        }
    }
}
