<?php

/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
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

namespace Magento\PaymentServicesPaypal\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\PaymentServicesBase\Model\HttpException;
use Magento\PaymentServicesPaypal\Helper\TrackingInformationProvider;
use Magento\PaymentServicesPaypal\Model\OrderService;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;

class SendTrackingInformation implements ObserverInterface
{
    /**
     * @var TransactionRepositoryInterface
     */
    private TransactionRepositoryInterface $transactionRepository;

    /**
     * @var TrackingInformationProvider
     */
    private TrackingInformationProvider $trackingInformationProvider;

    /**
     * @var OrderService
     */
    private OrderService $orderService;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param TransactionRepositoryInterface $transactionRepository
     * @param TrackingInformationProvider $trackingInformationProvider
     * @param OrderService $orderService
     * @param LoggerInterface $logger
     */
    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        TrackingInformationProvider    $trackingInformationProvider,
        OrderService                   $orderService,
        LoggerInterface                $logger,
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->trackingInformationProvider = $trackingInformationProvider;
        $this->orderService = $orderService;
        $this->logger = $logger;
    }

    /**
     * Send tracking information to Backend Service
     *
     * This observer is triggered when a shipment is created and the order has a capture transaction
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer): void
    {
        try {
            /** @var ShipmentInterface $shipment */
            $shipment = $observer->getEvent()->getShipment();
            if (empty($shipment->getTracks())) {
                return;
            }

            /** @var OrderInterface $order */
            $order = $shipment->getOrder();

            /** @var Payment $payment */
            $payment = $order->getPayment();
            if (!$payment || !$this->hasCaptureTransaction($payment)) {
                return;
            }

            $paypalOrderId = $payment->getAdditionalInformation('paypal_order_id');
            if (!$paypalOrderId) {
                $this->logger->error("Order doesn't have a paypal_order_id", [$order->getIncrementId()]);
                return;
            }

            $paypalTransactionId = $payment->getAdditionalInformation('paypal_txn_id');
            if (!$paypalTransactionId) {
                $this->logger->error("Order doesn't have a paypal_txn_id", [$order->getIncrementId()]);
                return;
            }

            $trackingInformation = $this->trackingInformationProvider->getTrackingInformation(
                $shipment,
                $paypalTransactionId
            );

            foreach ($trackingInformation as $tracking) {
                try {
                    $this->orderService->track($paypalOrderId, $tracking);
                } catch (HttpException $e) {
                    $this->logger->error(
                        'Error sending request to create tracking information',
                        ['exception' => $e->getMessage()]
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Error creating tracking information',
                ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Check if an order has at least one capture transaction
     *
     * @param Payment $payment
     * @return bool
     * @throws InputException
     */
    private function hasCaptureTransaction(Payment $payment): bool
    {
        $capture = $this->transactionRepository->getByTransactionType(
            TransactionInterface::TYPE_CAPTURE,
            $payment->getId()
        );

        return (bool)$capture;
    }
}
