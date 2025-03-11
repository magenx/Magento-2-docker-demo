<?php

/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2023 Adobe
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
 */

declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\PaymentServicesPaypal\Model\SmartButtons\Checkout\AddressConverter;
use Magento\PaymentServicesPaypal\Api\PaymentOrderManagementInterface;
use Magento\PaymentServicesPaypal\Helper\OrderHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\PaymentServicesPaypal\Api\Data\PaymentOrderInterfaceFactory;
use Magento\PaymentServicesPaypal\Api\Data\PaymentOrderDetailsInterface;
use Magento\PaymentServicesPaypal\Api\Data\PaymentOrderDetailsInterfaceFactory;
use Magento\PaymentServicesPaypal\Api\Data\PaymentSourceDetailsInterface;
use Magento\PaymentServicesPaypal\Api\Data\PaymentSourceDetailsInterfaceFactory;
use Magento\PaymentServicesPaypal\Api\Data\PaymentCardDetailsInterface;
use Magento\PaymentServicesPaypal\Api\Data\PaymentCardDetailsInterfaceFactory;
use Magento\PaymentServicesPaypal\Api\Data\PaymentCardBinDetailsInterface;
use Magento\PaymentServicesPaypal\Api\Data\PaymentCardBinDetailsInterfaceFactory;
use Magento\PaymentServicesBase\Model\Config as BaseConfig;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentOrderManagement implements PaymentOrderManagementInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     * @var OrderService
     */
    private OrderService $orderService;

    /**
     * @var PaymentOrderInterfaceFactory
     */
    private PaymentOrderInterfaceFactory $paymentOrderFactory;

    /**
     * @var PaymentOrderDetailsInterfaceFactory
     */
    private PaymentOrderDetailsInterfaceFactory $paymentOrderDetailsFactory;

    /**
     * @var PaymentSourceDetailsInterfaceFactory
     */
    private PaymentSourceDetailsInterfaceFactory $paymentSourceDetailsFactory;

    /**
     * @var PaymentCardDetailsInterfaceFactory
     */
    private PaymentCardDetailsInterfaceFactory $paymentCardDetailsFactory;

    /**
     * @var PaymentCardBinDetailsInterfaceFactory
     */
    private PaymentCardBinDetailsInterfaceFactory $paymentCardBinDetailsFactory;

    /**
     * @var string[]
     */
    private array $validMethodCodes;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var AddressConverter
     */
    private $addressConverter;

    /**
     * @var BaseConfig
     */
    private $config;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param OrderService $orderService
     * @param PaymentOrderInterfaceFactory $paymentOrderFactory
     * @param PaymentOrderDetailsInterfaceFactory $paymentOrderDetailsFactory
     * @param PaymentSourceDetailsInterfaceFactory $paymentSourceDetailsFactory
     * @param PaymentCardDetailsInterfaceFactory $paymentCardDetailsFactory
     * @param PaymentCardBinDetailsInterfaceFactory $paymentCardBinDetailsFactory
     * @param array $validMethodCodes
     * @param OrderHelper $orderHelper
     * @param AddressConverter $addressConverter
     * @param BaseConfig $config
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CartRepositoryInterface              $quoteRepository,
        OrderService                         $orderService,
        PaymentOrderInterfaceFactory         $paymentOrderFactory,
        PaymentOrderDetailsInterfaceFactory  $paymentOrderDetailsFactory,
        PaymentSourceDetailsInterfaceFactory $paymentSourceDetailsFactory,
        PaymentCardDetailsInterfaceFactory   $paymentCardDetailsFactory,
        PaymentCardBinDetailsInterfaceFactory $paymentCardBinDetailsFactory,
        array                                $validMethodCodes,
        OrderHelper                          $orderHelper,
        AddressConverter                     $addressConverter,
        BaseConfig $config
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->orderService = $orderService;
        $this->paymentOrderFactory = $paymentOrderFactory;
        $this->paymentOrderDetailsFactory = $paymentOrderDetailsFactory;
        $this->paymentSourceDetailsFactory = $paymentSourceDetailsFactory;
        $this->paymentCardDetailsFactory = $paymentCardDetailsFactory;
        $this->paymentCardBinDetailsFactory = $paymentCardBinDetailsFactory;
        $this->validMethodCodes = $validMethodCodes;
        $this->orderHelper = $orderHelper;
        $this->addressConverter = $addressConverter;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function create(
        string $methodCode,
        string $paymentSource,
        int    $cartId,
        string $location,
        bool   $vaultIntent = false,
        ?int    $customerId = null,
        ?string $threeDSMode = null
    ) {
        if (!in_array($methodCode, $this->validMethodCodes)) {
            throw new InvalidArgumentException(__('Invalid methodCode'));
        }
        $quote = $this->quoteRepository->getActive($cartId);

        $isLoggedIn = (bool)$customerId;
        $paymentMethod = $quote->getPayment();
        $paymentMethod->setAdditionalInformation('payment_source', $paymentSource);
        $paymentMethod->setAdditionalInformation('location', $location);
        $paymentMethod->setMethod($methodCode);
        $this->quoteRepository->save($quote);
        $orderIncrementId = $this->orderHelper->reserveAndGetOrderIncrementId($quote);

        $orderServiceResponse = $this->orderService->create(
            [
                'amount' => $this->orderHelper->formatAmount((float)$quote->getBaseGrandTotal()),
                /** @phpstan-ignore-next-line */
                'l2_data' => $this->orderHelper->getL2Data($quote, $paymentSource ?? ''),
                /** @phpstan-ignore-next-line */
                'l3_data' => $this->orderHelper->getL3Data($quote, $paymentSource ?? ''),
                'currency_code' => $quote->getCurrency()->getBaseCurrencyCode(),
                'is_digital' => $quote->isVirtual(),
                'website_id' => $quote->getStore()->getWebsiteId(),
                'payment_source' => $paymentSource,
                'quote_id' => $quote->getId(),
                'store_code' => $quote->getStoreId(),
                'payer' => $isLoggedIn
                    ? $this->orderService->buildPayer($quote, (string)$customerId)
                    : $this->orderService->buildGuestPayer($quote),
                'vault' => $vaultIntent,
                'shipping_address' => $this->orderService->mapAddress($quote->getShippingAddress()),
                'billing_address' => $this->orderService->mapAddress($quote->getBillingAddress()),
                'order_increment_id' => $orderIncrementId,
                'line_items' => $this->orderHelper->getLineItems($quote, $orderIncrementId),
                'amount_breakdown' => $this->orderHelper->getAmountBreakdown($quote, $orderIncrementId),
                'three_ds_mode' => $threeDSMode
            ]
        );

        if (isset($orderServiceResponse['paypal-order'])) {
            $paypalOrder = $orderServiceResponse['paypal-order'];
            $response = $this->paymentOrderFactory->create();
            $response->setId($paypalOrder['id'])
                ->setMpOrderId($paypalOrder['mp_order_id'])
                ->setStatus($paypalOrder['status'])
                ->setAmount((float)$this->orderHelper->formatAmount((float)$quote->getBaseGrandTotal()))
                ->setCurrencyCode($quote->getCurrency()->getBaseCurrencyCode());
            $paymentMethod->setAdditionalInformation('paypal_order_id', $paypalOrder['id']);
            $paymentMethod->setAdditionalInformation('payments_order_id', $paypalOrder['mp_order_id']);
            $paymentMethod->setAdditionalInformation('paypal_order_amount', $quote->getBaseGrandTotal());
            $paymentMethod->setAdditionalInformation(
                'payments_mode',
                $this->config->getEnvironmentType($quote->getStoreId())
            );
            $this->quoteRepository->save($quote);
            return $response;
        } else {
            $message = 'Failed to create an order';
            if (isset($orderServiceResponse['message'])) {
                $message = $message . ": " . $orderServiceResponse['message'];
            }
            throw new LocalizedException(__($message));
        }
    }

    /**
     * @inheritdoc
     */
    public function get(
        int $cartId,
        string $id,
        ?int $customerId = null
    ) {
        $quote = $this->quoteRepository->getActive($cartId);
        $orderId = $quote->getPayment()->getAdditionalInformation('paypal_order_id');

        // Check if passed in order ID is valid
        $message = 'Failed to get payment order';
        if ($id !== $orderId) {
            $message = $message . ": " . "order ID input is not valid" ;
            throw new LocalizedException(__($message));
        }

        $orderServiceResponse = $this->orderService->get($orderId);
        if (isset($orderServiceResponse['paypal-order'])) {
            $paypalOrder = $orderServiceResponse['paypal-order'];
            /** @var PaymentOrderDetailsInterface $response */
            $response = $this->paymentOrderDetailsFactory->create();
            $response->setId($paypalOrder['id'])
                ->setMpOrderId($paypalOrder['mp_order_id'])
                ->setStatus($paypalOrder['status']);
            if (isset($paypalOrder['payment_source_details'])) {
                $response->setPaymentSourceDetails(
                    $this->getPaymentSourceDetails($paypalOrder['payment_source_details'])
                );
            }
            return $response;
        } else {
            if (isset($orderServiceResponse['message'])) {
                $message = $message . ": " . $orderServiceResponse['message'];
            }
            throw new LocalizedException(__($message));
        }
    }

    /**
     * @inheritdoc
     */
    public function sync(
        int $cartId,
        string $id,
        ?int $customerId = null
    ) {
        $quote = $this->quoteRepository->getActive($cartId);
        $orderId = $quote->getPayment()->getAdditionalInformation('paypal_order_id');

        // Check if passed in order ID is valid
        $message = 'Failed to get payment order';
        if ($id !== $orderId) {
            $message = $message . ": " . "order ID input is not valid" ;
            throw new LocalizedException(__($message));
        }

        $orderServiceResponse = $this->orderService->get($orderId);

        if (isset($orderServiceResponse['paypal-order'])) {
            $shippingAddress = $this->addressConverter->convertShippingAddress($orderServiceResponse);
            $quote->getShippingAddress()->addData($shippingAddress)->setCollectShippingRates(true);
            $billingAddress = $this->addressConverter->convertBillingAddress($orderServiceResponse);
            $quote->getBillingAddress()->addData($billingAddress);
            $quote->setCustomerEmail($orderServiceResponse['paypal-order']['payer']['email']);
            $quote->getPayment()->setAdditionalInformation(
                'paypal_payer_id',
                $orderServiceResponse['paypal-order']['payer']['payer_id']
            );
            $quote->collectTotals();
            $this->quoteRepository->save($quote);
        } else {
            if (isset($orderServiceResponse['message'])) {
                $message = $message . ": " . $orderServiceResponse['message'];
            }
            throw new LocalizedException(__($message));
        }
        return true;
    }

    /**
     * Get Payment source details
     *
     * @param array $paymentSource
     * @return PaymentSourceDetailsInterface
     */
    private function getPaymentSourceDetails(array $paymentSource): PaymentSourceDetailsInterface
    {
        /** @var PaymentSourceDetailsInterface $paymentDetails */
        $paymentDetails = $this->paymentSourceDetailsFactory->create();
        $paymentDetails->setCard($this->getPaymentCardDetails($paymentSource['card']));
        return $paymentDetails;
    }

    /**
     * Get Payment card details
     *
     * @param array $paymentCard
     * @return PaymentCardDetailsInterface
     */
    private function getPaymentCardDetails(array $paymentCard): PaymentCardDetailsInterface
    {
        /** @var PaymentCardDetailsInterface $paymentCardDetails */
        $paymentCardDetails = $this->paymentCardDetailsFactory->create();
        $paymentCardDetails->setName($paymentCard['name']);
        $paymentCardDetails->setLastDigits($paymentCard['last_digits']);
        $paymentCardDetails->setCardExpiryMonth($paymentCard['card_expiry_month']);
        $paymentCardDetails->setCardExpiryYear($paymentCard['card_expiry_year']);
        $paymentCardDetails->setBinDetails($this->getPaymentCardBinDetails($paymentCard['bin_details']));
        return $paymentCardDetails;
    }

    /**
     * Get Payment card bin details
     *
     * @param array $paymentCardBin
     * @return PaymentCardBinDetailsInterface
     */
    private function getPaymentCardBinDetails(array $paymentCardBin): PaymentCardBinDetailsInterface
    {
        /** @var PaymentCardBinDetailsInterface $paymentCardBinDetails */
        $paymentCardBinDetails = $this->paymentCardBinDetailsFactory->create();
        $paymentCardBinDetails->setBin($paymentCardBin['bin']);
        return $paymentCardBinDetails;
    }
}
