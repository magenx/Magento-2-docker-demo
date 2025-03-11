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

namespace Magento\PaymentServicesPaypal\Cron;

use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\PaymentServicesPaypal\Model\Provider\OrdersWithPaymentReview;
use Magento\PaymentServicesPaypal\Model\Payment\PaymentManagement;
use Magento\PaymentServicesPaypal\Model\Config;

class SyncOrderPaymentStatus
{
    private const NUMBER_OF_ORDERS_TO_REFRESH = 25;
    private const LAST_REFRESH_ATTEMPT = 'last_refresh_attempt';
    private const REFRESH_ATTEMPT_COUNT = 'refresh_attempt_count';
    private const REFRESH_INTERVAL_MULTIPLIER = 60;
    private const MAX_REFRESH_INTERVAL_IN_SECONDS = 7200;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var OrdersWithPaymentReview
     */
    private OrdersWithPaymentReview $ordersWithPaymentReview;

    /**
     * @var PaymentManagement
     */
    private PaymentManagement $paymentManagement;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrdersWithPaymentReview $ordersWithPaymentReview
     * @param PaymentManagement $paymentManagement
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrdersWithPaymentReview $ordersWithPaymentReview,
        PaymentManagement $paymentManagement,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->ordersWithPaymentReview = $ordersWithPaymentReview;
        $this->paymentManagement = $paymentManagement;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Refresh payment status for orders in payment review status
     *
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(): void
    {
        if (!$this->shouldRun()) {
            return;
        }

        $this->logger->debug('Refreshing payment status for orders in Payment Review status...');

        $orders = $this->ordersWithPaymentReview->get(self::NUMBER_OF_ORDERS_TO_REFRESH);

        foreach ($orders as $order) {
            if (!$this->isTimeToRefresh($order->getPayment())) {
                $this->logger->debug(
                    'Payment status for order ' . $order->getIncrementId() . ' was not refreshed due to backoff period'
                );
                continue;
            }

            $this->recordPaymentRefreshAttempt($order->getPayment());
            $wasRefreshed = $this->paymentManagement->refreshPaymentStatus($order->getPayment());

            if ($wasRefreshed) {
                $this->logger->debug('Payment status for order ' . $order->getIncrementId() . ' was refreshed');
            } else {
                $this->logger->debug('Payment status for order ' . $order->getIncrementId() . ' was not refreshed');
            }

            $this->orderRepository->save($order);
        }
    }

    /**
     * Check if the cron job should run
     *
     * @return bool
     */
    private function shouldRun(): bool
    {
        return $this->config->isAsyncPaymentStatusUpdatesEnabled();
    }

    /**
     * Check if it is time to refresh the payment status
     *
     * The backoff period increases by a factor of 60 seconds for each attempt, up to a maximum of 2 hours
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return bool
     */
    private function isTimeToRefresh(\Magento\Sales\Model\Order\Payment $payment): bool
    {
        $lastRefreshAttempt = $payment->getAdditionalInformation(self::LAST_REFRESH_ATTEMPT) ?? 0;
        $attemptCount = $payment->getAdditionalInformation(self::REFRESH_ATTEMPT_COUNT) ?? 0;

        return time() - $lastRefreshAttempt
            > min(
                $attemptCount * self::REFRESH_INTERVAL_MULTIPLIER,
                self::MAX_REFRESH_INTERVAL_IN_SECONDS
            );
    }

    /**
     * Record the payment refresh attempt
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return void
     */
    private function recordPaymentRefreshAttempt(\Magento\Sales\Model\Order\Payment $payment): void
    {
        $payment->setAdditionalInformation(self::LAST_REFRESH_ATTEMPT, time());
        $attemptCount = $payment->getAdditionalInformation(self::REFRESH_ATTEMPT_COUNT) ?? 0;
        $payment->setAdditionalInformation(self::REFRESH_ATTEMPT_COUNT, ++$attemptCount);
    }
}
