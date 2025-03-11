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

namespace Magento\PaymentServicesPaypal\Model\Payment;

use Magento\PaymentServicesPaypal\Model\OrderService;
use Magento\Sales\Model\Order;
use Magento\PaymentServicesBase\Model\HttpException;
use Magento\Framework\Exception\LocalizedException;

class PaymentManagement
{
    private const OFFLINE_UPDATE = false;

    /**
     * @var OrderService
     */
    private OrderService $orderService;

    /**
     * @param OrderService $orderService
     */
    public function __construct(
        OrderService $orderService
    ) {
        $this->orderService = $orderService;
    }

    /**
     * Refreshes the payment status from PayPal and updates the payment and order accordingly
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return bool
     * @throws \InvalidArgumentException|HttpException|LocalizedException
     */
    public function refreshPaymentStatus(\Magento\Sales\Model\Order\Payment $payment): bool
    {
        $orderId = $this->extractPayPalOrderIdFromPayment($payment);
        $payPalTxnId = $this->extractPayPalTxnIdFromPayment($payment);

        $captureTransaction = $this->extractCaptureTransaction(
            $this->orderService->get($orderId),
            $payPalTxnId
        );

        if ($this->isStillPending($captureTransaction)) {
            return false;
        }

        if ($this->canApprovePayment($captureTransaction)) {
            $this->approvePayment($payment);
            return true;
        }

        $this->declinePayment($payment);
        return true;
    }

    /**
     * Extracts the PayPal order id from the payment
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return string
     */
    private function extractPayPalOrderIdFromPayment(\Magento\Sales\Model\Order\Payment $payment): string
    {
        $orderId = $payment->getAdditionalInformation('paypal_order_id');
        if (empty($orderId)) {
            throw new \InvalidArgumentException("payment is missing paypal_order_id");
        }
        return $orderId;
    }

    /**
     * Extracts the PayPal transaction id that caused Payment to be pending
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return string
     */
    private function extractPayPalTxnIdFromPayment(\Magento\Sales\Model\Order\Payment $payment): string
    {
        $txnId = $payment->getAdditionalInformation('pending_txn_id');
        if (empty($txnId)) {
            throw new \InvalidArgumentException("payment is missing pending_txn_id");
        }
        return $txnId;
    }

    /**
     * Extracts the capture transaction from the order
     *
     * @param array $orderData
     * @param string $payPalTxnId
     * @return array
     */
    private function extractCaptureTransaction(array $orderData, string $payPalTxnId): array
    {
        if (!isset($orderData['paypal-order']['purchase_units'])) {
            throw new \InvalidArgumentException(
                "could not extract transaction from order, order is missin purchase units"
            );
        }

        foreach ($orderData['paypal-order']['purchase_units'] as $purchaseUnit) {
            if (!isset($purchaseUnit['payments']['captures'])) {
                throw new \InvalidArgumentException(
                    "could not extract transaction from order, purchase unit is missing captures"
                );
            }

            foreach ($purchaseUnit['payments']['captures'] as $capture) {
                if ($capture['id'] === $payPalTxnId) {
                    return $capture;
                }
            }
        }

        throw new \InvalidArgumentException(sprintf(
            "could not extract transaction from order, no capture transaction found with id %s",
            $payPalTxnId
        ));
    }

    /**
     * Checks if the capture transaction is still pending
     *
     * @param array $captureTransaction
     * @return bool
     */
    private function isStillPending(array $captureTransaction): bool
    {
        return strcasecmp($captureTransaction['status'], 'PENDING') === 0;
    }

    /**
     * Checks if the payment can be approved
     *
     * @param array $captureTransaction
     * @return bool
     */
    private function canApprovePayment(array $captureTransaction): bool
    {
        return strcasecmp($captureTransaction['status'], 'COMPLETED') === 0;
    }

    /**
     * Approves the payment
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     */
    private function approvePayment(\Magento\Sales\Model\Order\Payment $payment): void
    {
        $this->fixMissingTransactionId($payment);

        $payment->setIsTransactionPending(false);
        $payment->setIsTransactionApproved(true);
        $payment->update(self::OFFLINE_UPDATE);
    }

    /**
     * Declines the payment
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     */
    private function declinePayment(\Magento\Sales\Model\Order\Payment $payment): void
    {
        $this->fixMissingTransactionId($payment);

        $payment->setIsTransactionPending(false);
        $payment->setIsTransactionDenied(true);
        $payment->update(self::OFFLINE_UPDATE);
    }

    /**
     * Fixes the missing transaction id
     *
     * For some reason Payment object is missing transaction id, so we need to set it from last transaction
     * We need to do this because the transaction id is used to update the payment invoice
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     */
    private function fixMissingTransactionId(\Magento\Sales\Model\Order\Payment $payment): void
    {
        if (empty($payment->getTransactionId())) {
            $payment->setTransactionId($payment->getLastTransId());
        }
    }
}
