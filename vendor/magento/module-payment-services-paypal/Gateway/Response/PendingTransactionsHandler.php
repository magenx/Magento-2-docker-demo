<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Gateway\Response;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Psr\Log\LoggerInterface;
use Magento\PaymentServicesPaypal\Model\Config;

class PendingTransactionsHandler implements HandlerInterface
{
    public const CAPTURE_TXN = 'capture';
    public const AUTH_CAPTURE_TXN = 'auth_capture';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Json
     */
    private Json $serializer;

    /**
     * @param LoggerInterface $logger
     * @param Config $config
     * @param Json $serializer
     */
    public function __construct(
        LoggerInterface $logger,
        Config $config,
        Json $serializer
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->serializer = $serializer;
    }

    /**
     * Handles transactions in pending status
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response): void
    {
        if (!$this->config->isAsyncPaymentStatusUpdatesEnabled()
            || !$this->isSupportedTransactionType($response)) {
            return;
        }

        if (!$this->hasPaymentData($handlingSubject)) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        if ($this->isPendingTransaction($response)) {
            /** @var PaymentDataObjectInterface $paymentDO */
            $paymentDO = $handlingSubject['payment'];

            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $paymentDO->getPayment();

            $payment->setIsTransactionPending(true);
            $payment->setAdditionalInformation('pending_txn_id', $this->extractPayPalTransactionId($response));
        }
    }

    /**
     * Checks if payment data is provided
     *
     * @param array $handlingSubject
     * @return bool
     */
    private function hasPaymentData(array $handlingSubject): bool
    {
        return isset($handlingSubject['payment'])
            && $handlingSubject['payment'] instanceof PaymentDataObjectInterface;
    }

    /**
     * Checks if transaction type is supported
     *
     * @param array $response
     * @return bool
     */
    private function isSupportedTransactionType(array $response): bool
    {
        return isset($response['mp-transaction']['type'])
            && (
                $response['mp-transaction']['type'] === self::CAPTURE_TXN
                || $response['mp-transaction']['type'] === self::AUTH_CAPTURE_TXN
            );
    }

    /**
     * Checks if transaction is in pending status
     *
     * @param array $response
     * @return bool
     */
    private function isPendingTransaction(array $response): bool
    {
        return strcasecmp($this->extractTransactionStatus($response), 'pending') === 0;
    }

    /**
     * Extracts transaction status from the response
     *
     * Capture and auth_capture transactions have different response structures
     * hence the need for different extraction methods
     *
     * @param array $response
     * @return string
     */
    private function extractTransactionStatus(array $response): string
    {
        try {
            if ($response['mp-transaction']['type'] === self::CAPTURE_TXN) {
                return $this->extractCaptureTransactionStatus($response);
            }

            return $this->extractAuthCaptureTransactionStatus($response);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf("could not extract transaction status from response: %s", $e->getMessage())
            );

            return '';
        }
    }

    /**
     * Extracts transaction status from the capture transaction response
     *
     * @param array $response
     * @return string
     */
    private function extractCaptureTransactionStatus(array $response): string
    {
        $message = $this->extractPayPalMessage($response);

        if (!isset($message['status'])) {
            throw new \InvalidArgumentException('status is not set in the message');
        }

        return $message['status'];
    }

    /**
     * Extracts transaction status from the auth_capture transaction response
     *
     * @param array $response
     * @return string
     */
    private function extractAuthCaptureTransactionStatus(array $response): string
    {
        $txnId = $this->extractPayPalTransactionId($response);
        $message = $this->extractPayPalMessage($response);
        $transaction = $this->extractCaptureTransactionFromPayPalResponse($txnId, $message);

        if (!isset($transaction['status'])) {
            throw new \InvalidArgumentException('status is not set in the PayPal transaction');
        }

        return $transaction['status'];
    }

    /**
     * Extracts capture transaction from the PayPal response
     *
     * @param string $txnId
     * @param array $message
     * @return array
     */
    private function extractCaptureTransactionFromPayPalResponse(string $txnId, array $message): array
    {
        if (!isset($message['purchase_units'])) {
            throw new \InvalidArgumentException('purchase units are not set in the message');
        }

        foreach ($message['purchase_units'] as $purchaseUnit) {
            if (!isset($purchaseUnit['payments']['captures'])) {
                throw new \InvalidArgumentException('captures are not set in the purchase unit');
            }

            foreach ($purchaseUnit['payments']['captures'] as $capture) {
                if (!isset($capture['id'])) {
                    throw new \InvalidArgumentException('id is not set in the capture');
                }

                if ($capture['id'] === $txnId) {
                    return $capture;
                }
            }
        }

        return [];
    }

    /**
     * Extracts PayPal transaction id from the response
     *
     * @param array $response
     * @return string
     */
    private function extractPayPalTransactionId(array $response): string
    {
        if (!isset($response['mp-transaction']['paypal_transaction_id'])) {
            throw new \InvalidArgumentException('paypal_transaction_id is not set in the response');
        }

        return $response['mp-transaction']['paypal_transaction_id'];
    }

    /**
     * Extracts message from the response
     *
     * @param array $response
     * @return array
     */
    private function extractPayPalMessage(array $response): array
    {
        if (!isset($response['mp-transaction']['message'])) {
            throw new \InvalidArgumentException('message is not set in the response');
        }

        return $this->serializer->unserialize($response['mp-transaction']['message']);
    }
}
