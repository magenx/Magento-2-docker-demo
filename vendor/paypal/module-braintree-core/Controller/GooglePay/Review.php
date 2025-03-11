<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Controller\GooglePay;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartInterface;
use PayPal\Braintree\Model\GooglePay\Config;
use PayPal\Braintree\Model\GooglePay\Helper\QuoteUpdater;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\Page;
use PayPal\Braintree\Observer\DataAssignObserver;
use PayPal\Braintree\Observer\GooglePay\DataAssignObserver as GooglePayDataAssignObserver;

/**
 * Google Pay review order block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Review extends AbstractAction implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * Request constants
     */
    private const REQUEST_NONCE = 'nonce';
    private const REQUEST_IS_NETWORK_TOKENIZED = 'isNetworkTokenized';
    private const REQUEST_DEVICE_DATA = 'deviceData';
    private const REQUEST_DETAILS = 'details';

    /**
     * @var QuoteUpdater
     */
    private QuoteUpdater $quoteUpdater;

    /**
     * @var SerializerInterface
     */
    protected SerializerInterface $serializer;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Config $config
     * @param Session $checkoutSession
     * @param QuoteUpdater $quoteUpdater
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        Config $config,
        Session $checkoutSession,
        QuoteUpdater $quoteUpdater,
        SerializerInterface $serializer
    ) {
        parent::__construct($context, $config, $checkoutSession);
        $this->serializer = $serializer;
        $this->quoteUpdater = $quoteUpdater;
    }

    /**
     * @inheritdoc
     */
    public function execute(): Page|Redirect
    {
        $requestData = $this->serializer->unserialize($this->getRequest()->getPostValue('result', '{}'));

        try {
            $quote = $this->checkoutSession->getQuote();
            $this->validateQuote($quote);

            if ($this->validateRequestData($requestData)) {
                $this->quoteUpdater->execute(
                    $requestData[self::REQUEST_NONCE],
                    $requestData[self::REQUEST_IS_NETWORK_TOKENIZED],
                    $requestData[self::REQUEST_DEVICE_DATA] ?? '',
                    $requestData[self::REQUEST_DETAILS],
                    $quote
                );
            } elseif (!$this->validateQuotePaymentAdditionalInformation($quote)) {
                throw new LocalizedException(__("We can't initialize checkout."));
            }

            /** @var Page $resultPage */
            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

            /** @var \PayPal\Braintree\Block\GooglePay\Checkout\Review $reviewBlock */
            $reviewBlock = $resultPage->getLayout()->getBlock('braintree.googlepay.review');

            $reviewBlock->setQuote($quote);
            $reviewBlock->getChildBlock('shipping_method')->setData('quote', $quote);

            return $resultPage;
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
    }

    /**
     * Validate request data
     *
     * @param array $requestData
     * @return bool
     */
    private function validateRequestData(array $requestData): bool
    {
        return !empty($requestData[self::REQUEST_NONCE])
            && isset($requestData[self::REQUEST_IS_NETWORK_TOKENIZED])
            && is_bool($requestData[self::REQUEST_IS_NETWORK_TOKENIZED])
            && !empty($requestData[self::REQUEST_DETAILS]);
    }

    /**
     * Validate that a quote has the nonce and the is_card_network tokenized params set.
     *
     * @param CartInterface $quote
     * @return bool
     */
    private function validateQuotePaymentAdditionalInformation(CartInterface $quote): bool
    {
        $payment = $quote->getPayment();
        if (!$payment->getAdditionalInformation(DataAssignObserver::PAYMENT_METHOD_NONCE)) {
            return false;
        }

        if ($payment->getAdditionalInformation(GooglePayDataAssignObserver::IS_CARD_NETWORK_TOKENIZED) === null) {
            return false;
        }

        return true;
    }
}
