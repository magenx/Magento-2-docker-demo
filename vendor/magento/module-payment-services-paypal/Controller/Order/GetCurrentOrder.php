<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Controller\Order;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\PaymentServicesPaypal\Model\OrderService;
use Magento\PaymentServicesBase\Model\HttpException;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Webapi\Rest\Response as WebapiResponse;
use Magento\Framework\Session\Generic as PaypalSession;
use Magento\Quote\Model\QuoteRepository;

class GetCurrentOrder implements HttpGetActionInterface
{
    /**
     * @param CheckoutSession $checkoutSession
     * @param OrderService $orderService
     * @param ResultFactory $resultFactory
     * @param PaypalSession $paypalSession
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        private CheckoutSession $checkoutSession,
        private OrderService $orderService,
        private ResultFactory $resultFactory,
        private PayPalSession $paypalSession,
        private QuoteRepository $quoteRepository,
    ) {
    }

    /**
     * Gets Order details from SaaS based on order id inside current quote object
     *
     * @return ResultInterface
     */
    public function execute() : ResultInterface
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $quote = $this->checkoutSession->getQuote();

            if ($this->paypalSession->getQuoteId()) {
                $quote = $this->quoteRepository->getActive($this->paypalSession->getQuoteId());
            }

            $paypalOrderId = $quote->getPayment()->getAdditionalInformation('paypal_order_id');

            if (!$paypalOrderId) {
                $result->setHttpResponseCode(WebapiException::HTTP_NOT_FOUND);
                return $result;
            }

            $response = $this->orderService->get($paypalOrderId);
            $result->setHttpResponseCode(WebapiResponse::HTTP_OK)
                ->setData(['response' => $response]);
        } catch (HttpException $e) {
            $result->setHttpResponseCode(WebapiException::HTTP_INTERNAL_ERROR);
        }

        return $result;
    }
}
