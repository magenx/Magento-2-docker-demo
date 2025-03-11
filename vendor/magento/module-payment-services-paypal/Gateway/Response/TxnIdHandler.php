<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;

class TxnIdHandler implements HandlerInterface
{
    public const AUTH_ID_KEY = 'auth_txn_id';
    public const PAYPAL_TXN_ID_KEY = 'paypal_txn_id';
    public const AUTH_TXN = 'authorization';
    public const CAPTURE_TXN = 'capture';
    public const AUTH_CAPTURE_TXN = 'auth_capture';

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setTransactionId($response['mp-transaction']['id']);
        if ($response['mp-transaction']['type'] === self::AUTH_TXN) {
            $quoteId = $payment->getOrder()->getQuoteId();
            $quote = $this->quoteRepository->get($quoteId);
            $quotePayment = $quote->getPayment();
            $quotePayment->setAdditionalInformation(self::AUTH_ID_KEY, $response['mp-transaction']['id']);
            $this->quoteRepository->save($quote);
            $payment->setAdditionalInformation(self::AUTH_ID_KEY, $response['mp-transaction']['id']);
        }

        if ($response['mp-transaction']['type'] === self::AUTH_TXN
            || $response['mp-transaction']['type'] === self::CAPTURE_TXN
            || $response['mp-transaction']['type'] === self::AUTH_CAPTURE_TXN) {

            if (isset($response['mp-transaction']['paypal_transaction_id'])) {
                $payment->setAdditionalInformation(
                    self::PAYPAL_TXN_ID_KEY,
                    $response['mp-transaction']['paypal_transaction_id'] ?? null
                );
            } else {
                $this->logger->warning('No PayPal transaction ID found in payment source response', $response);
            }
        }

        $payment->setIsTransactionClosed(false);
    }
}
