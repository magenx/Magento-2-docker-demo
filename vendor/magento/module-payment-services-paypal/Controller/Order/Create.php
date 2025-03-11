<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Controller\Order;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\PaymentServicesPaypal\Helper\OrderHelper;
use Magento\PaymentServicesPaypal\Model\OrderService;
use Magento\PaymentServicesBase\Model\HttpException;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;

class Create implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private const VAULT_PARAM_KEY = 'vault';

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var QuoteRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param OrderService $orderService
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param QuoteRepositoryInterface $quoteRepository
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        OrderService $orderService,
        ResultFactory $resultFactory,
        RequestInterface $request,
        QuoteRepositoryInterface $quoteRepository,
        OrderHelper $orderHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->orderService = $orderService;
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->quoteRepository = $quoteRepository;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Dispatch the order creation request with Commerce params
     *
     * @return ResultInterface
     */
    public function execute() : ResultInterface
    {
        $shouldCardBeVaulted = $this->request->getParam(self::VAULT_PARAM_KEY) === 'true';
        $paymentSource = $this->request->getPost('payment_source');
        $threeDsMode = $this->request->getPost('three_ds_mode');
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $quote = $this->checkoutSession->getQuote();
            $quoteId = $quote->getId();
            $isLoggedIn = $this->customerSession->isLoggedIn();
            $orderIncrementId = $this->orderHelper->reserveAndGetOrderIncrementId($quote);

            $response = $this->orderService->create(
                [
                    'amount' => $this->orderHelper->formatAmount((float)$quote->getBaseGrandTotal()),
                    'l2_data' => $this->orderHelper->getL2Data($quote, $paymentSource ?? ''),
                    'l3_data' => $this->orderHelper->getL3Data($quote, $paymentSource ?? ''),
                    'currency_code' => $quote->getCurrency()->getBaseCurrencyCode(),
                    'shipping_address' => $this->orderService->mapAddress($quote->getShippingAddress()),
                    'billing_address' => $this->orderService->mapAddress($quote->getBillingAddress()),
                    'payer' => $isLoggedIn
                        ? $this->orderService->buildPayer($quote, $this->customerSession->getCustomer()->getId())
                        : $this->orderService->buildGuestPayer($quote),
                    'is_digital' => $quote->isVirtual(),
                    'website_id' => $quote->getStore()->getWebsiteId(),
                    'three_ds_mode' => $threeDsMode,
                    'payment_source' => $paymentSource,
                    'vault' => $shouldCardBeVaulted,
                    'quote_id' => $quoteId,
                    'order_increment_id' => $orderIncrementId,
                    'line_items' => $this->orderHelper->getLineItems($quote, $orderIncrementId),
                    'amount_breakdown' => $this->orderHelper->getAmountBreakdown($quote, $orderIncrementId),
                ]
            );

            $response = array_merge_recursive(
                $response,
                [
                    "paypal-order" => [
                        "amount" => $quote->getBaseGrandTotal(),
                        "currency_code" => $quote->getCurrency()->getBaseCurrencyCode()
                    ]
                ]
            );

            if (isset($response["paypal-order"]['id'])) {
                $quote->getPayment()->setAdditionalInformation('paypal_order_id', $response["paypal-order"]['id']);
                $quote->getPayment()->setAdditionalInformation('paypal_order_amount', $quote->getBaseGrandTotal());
                $this->quoteRepository->save($quote);
            }

            $result->setHttpResponseCode($response['status'])
                ->setData(['response' => $response]);
        } catch (HttpException $e) {
            $result->setHttpResponseCode(500);
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request) :? InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request) :? bool
    {
        return true;
    }
}
