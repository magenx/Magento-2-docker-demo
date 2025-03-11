<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Controller\SmartButtons;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Session\Generic as PaypalSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;

class Cancel implements HttpGetActionInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PaypalSession
     */
    private $paypalSession;

    /**
     * @param ResultFactory $resultFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     * @param PaypalSession $paypalSession
     */
    public function __construct(
        ResultFactory $resultFactory,
        CartRepositoryInterface $quoteRepository,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger,
        PaypalSession $paypalSession
    ) {
        $this->resultFactory = $resultFactory;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->paypalSession = $paypalSession;
    }

    /**
     * Save current quote and redirect to cart page
     *
     * @return ResultInterface
     */
    public function execute() : ResultInterface
    {
        try {
            $quote = $this->quoteRepository->get($this->paypalSession->getQuoteId());
            $customerQuote = $this->checkoutSession->getQuote();
            if ($quote->getId()) {
                $customerQuote->merge($quote);
                $customerQuote->collectTotals();
                $this->quoteRepository->save($customerQuote);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        $this->paypalSession->unsCustomerQuoteId();
        $this->paypalSession->unsQuoteId();
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/cart');
    }
}
