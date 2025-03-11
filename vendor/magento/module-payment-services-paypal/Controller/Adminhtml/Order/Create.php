<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session\Quote as QuoteSession;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\PaymentServicesBase\Model\HttpException;
use Magento\PaymentServicesPaypal\Helper\OrderHelper;
use Magento\PaymentServicesPaypal\Model\OrderService;
use Magento\Quote\Model\Quote\Address as Address;
use Magento\ServiceProxy\Controller\Adminhtml\AbstractProxyController;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;

class Create extends AbstractProxyController implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_PaymentServicesPaypal::ordercreate';

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var QuoteSession
     */
    private $quoteSession;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var QuoteRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param Context $context
     * @param QuoteSession $quoteSession
     * @param OrderService $orderService
     * @param OrderHelper $orderHelper
     * @param QuoteRepositoryInterface $quoteRepository
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        QuoteSession $quoteSession,
        OrderService $orderService,
        OrderHelper $orderHelper,
        QuoteRepositoryInterface $quoteRepository,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
        $this->quoteSession = $quoteSession;
        $this->orderService = $orderService;
        $this->orderHelper = $orderHelper;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $quote = $this->quoteSession->getQuote();
            $customerId = $quote->getCustomerId();
            $payer = $customerId !== null && $customerId != ""
                ? $this->orderService->buildPayer($quote, (string)$customerId)
                : $this->orderService->buildGuestPayer($quote);
            $paymentSource = $this->getRequest()->getPost('payment_source');
            $orderIncrementId = $this->resolveOrderIncrementId($quote);

            $response = $this->orderService->create(
                [
                    'amount' => $this->orderHelper->formatAmount((float)$quote->getBaseGrandTotal()),
                    'l2_data' => $this->orderHelper->getL2Data($quote, $paymentSource ?? ''),
                    'l3_data' => $this->orderHelper->getL3Data($quote, $paymentSource ?? ''),
                    'currency_code' => $quote->getCurrency()->getBaseCurrencyCode(),
                    'shipping_address' => $this->orderService->mapAddress($quote->getShippingAddress()),
                    'billing_address' => $this->orderService->mapAddress($quote->getBillingAddress()),
                    'payer' => $payer,
                    'is_digital' => $quote->isVirtual(),
                    'website_id' => $quote->getStore()->getWebsiteId(),
                    'store_code' => $quote->getStore()->getCode(),
                    'payment_source' => $paymentSource,
                    'quote_id' => $quote->getId(),
                    'order_increment_id' => $orderIncrementId,
                    'line_items' => $this->orderHelper->getLineItems($quote, $orderIncrementId),
                    'amount_breakdown' => $this->orderHelper->getAmountBreakdown($quote, $orderIncrementId),
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
            $result->setData($e->getMessage());
        }
        return $result;
    }

    /**
     * Resolve the order increment ID
     *
     * If the order is being reordered, the new order increment ID is based on the original order increment ID
     * and the call to $quote->reserveOrderId() is ignored.
     *
     * @see \Magento\Sales\Model\AdminOrder\Create::beforeSubmit
     *
     * @param Quote $quote
     * @return string
     */
    private function resolveOrderIncrementId(Quote $quote): string
    {
        if ($this->quoteSession->getReordered()) {
            return $this->generateIncrementIdFromParent();
        }

        return $this->orderHelper->reserveAndGetOrderIncrementId($quote);
    }

    /**
     * Generate the new order increment ID based on the original order
     *
     * @return string
     */
    private function generateIncrementIdFromParent(): string
    {
        $oldOrder = $this->orderRepository->get($this->quoteSession->getReordered());
        $originalId = $oldOrder->getOriginalIncrementId();
        if (!$originalId) {
            $originalId = $oldOrder->getIncrementId();
        }
        $orderEditIncrement = $oldOrder->getEditIncrement() + 1;

        return $originalId . '-' . $orderEditIncrement;
    }
}
