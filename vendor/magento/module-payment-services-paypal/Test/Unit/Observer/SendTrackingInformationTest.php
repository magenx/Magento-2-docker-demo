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

namespace Magento\PaymentServicesPaypal\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\PaymentServicesPaypal\Helper\TrackingInformationProvider;
use Magento\PaymentServicesPaypal\Model\OrderService;
use Magento\PaymentServicesPaypal\Observer\SendTrackingInformation;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction\Repository as TransactionRepository;
use Magento\Sales\Model\Order\Shipment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SendTrackingInformationTest extends TestCase
{
    private const PAYPAL_TRANSACTION_ID = 'paypal_transaction_id';
    private const ORDER_INCREMENT_ID = 'order_increment_id';
    private const PAYPAL_ORDER_ID = 'paypal_order_id';
    private const PAYMENT_ID = 'payment_id';

    /**
     * @var MockObject|TransactionRepository
     */
    private $transactionRepository;

    /**
     * @var MockObject|TrackingInformationProvider
     */
    private $trackingInformationProvider;

    /**
     * @var MockObject|OrderService
     */
    private $orderService;

    /**
     * @var SendTrackingInformation
     */
    private $sendTrackingInformation;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->transactionRepository = $this->createMock(TransactionRepository::class);
        $this->trackingInformationProvider = $this->createMock(TrackingInformationProvider::class);
        $this->orderService = $this->createMock(OrderService::class);

        $this->sendTrackingInformation = new SendTrackingInformation(
            $this->transactionRepository,
            $this->trackingInformationProvider,
            $this->orderService,
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * @return void
     */
    public function testSendTrackingInformationSuccessfully(): void
    {
        $shipment = $this->createShipment();
        $observer = $this->createEventObserver($shipment);

        $this->transactionRepository->expects($this->once())
            ->method('getByTransactionType')
            ->with(TransactionInterface::TYPE_CAPTURE, self::PAYMENT_ID)
            ->willReturn("capture");

        $this->trackingInformationProvider->expects($this->once())
            ->method('getTrackingInformation')
            ->with($shipment, self::PAYPAL_TRANSACTION_ID)
            ->willReturn($this->createTrackingInformation());

        $this->orderService->expects($this->exactly(2))
            ->method('track');

        $this->sendTrackingInformation->execute($observer);
    }

    /**
     * @return void
     */
    public function testTrackingInformationIsNotSentBecauseNoCaptureFound(): void
    {
        $payment = $this->createPayment(false);
        $order = $this->createOrder($payment);
        $shipment = $this->createShipment($order);

        $observer = $this->createEventObserver($shipment);

        $this->transactionRepository->expects($this->once())
            ->method('getByTransactionType')
            ->with(TransactionInterface::TYPE_CAPTURE, self::PAYMENT_ID)
            ->willReturn(null);

        $this->trackingInformationProvider->expects($this->never())
            ->method('getTrackingInformation');

        $this->orderService->expects($this->never())
            ->method('track');

        $this->sendTrackingInformation->execute($observer);
    }

    /**
     * @return void
     */
    public function testTrackingInformationIsNotSentBecauseNoTrackInShipment(): void
    {
        $shipment = $this->getMockBuilder(Shipment::class)
            ->onlyMethods([
                'getOrder',
                'getTracks',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $shipment->expects($this->once())
            ->method('getTracks')
            ->willReturn([]);

        $observer = $this->createEventObserver($shipment);

        $this->transactionRepository->expects($this->never())
            ->method('getByTransactionType');

        $this->trackingInformationProvider->expects($this->never())
            ->method('getTrackingInformation');

        $this->orderService->expects($this->never())
            ->method('track');

        $this->sendTrackingInformation->execute($observer);
    }

    /**
     * @return void
     */
    public function testTrackingInformationIsNotSentBecauseNoTrackingInformation(): void
    {
        $shipment = $this->createShipment();
        $observer = $this->createEventObserver($shipment);

        $this->transactionRepository->expects($this->once())
            ->method('getByTransactionType')
            ->with(TransactionInterface::TYPE_CAPTURE, self::PAYMENT_ID)
            ->willReturn("capture");

        $this->trackingInformationProvider->expects($this->once())
            ->method('getTrackingInformation')
            ->with($shipment, self::PAYPAL_TRANSACTION_ID)
            ->willReturn([]);

        $this->orderService->expects($this->never())
            ->method('track');

        $this->sendTrackingInformation->execute($observer);
    }

    /**
     * Create a shipment
     *
     * @param OrderInterface|null $order
     * @return Shipment
     */
    private function createShipment(?OrderInterface $order = null): Shipment
    {
        if (!$order) {
            $order = $this->createOrder();
        }

        $shipment = $this->getMockBuilder(Shipment::class)
            ->onlyMethods([
                'getOrder',
                'getTracks',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $shipment->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);

        $shipment->expects($this->once())
            ->method('getTracks')
            ->willReturn([
                $this->createMock(ShipmentTrackInterface::class),
                $this->createMock(ShipmentTrackInterface::class),
            ]);

        return $shipment;
    }

    /**
     * Create an event observer
     *
     * @param Shipment $shipment
     * @return EventObserver
     */
    private function createEventObserver(Shipment $shipment): EventObserver
    {
        $observer = $this->createMock(EventObserver::class);
        $event = $this->getMockBuilder(Event::class)
            ->addMethods([
                'getShipment',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getShipment')
            ->willReturn($shipment);

        $observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($event);

        return $observer;
    }

    /**
     * Create an order with increment id and a payment
     *
     * @param Payment|null $payment
     * @return OrderInterface
     */
    private function createOrder(?Payment $payment = null): OrderInterface
    {
        if (!$payment) {
            $payment = $this->createPayment();
        }

        $order = $this->createMock(OrderInterface::class);

        $order->expects($this->any())
            ->method('getIncrementId')
            ->willReturn(self::ORDER_INCREMENT_ID);

        $order->expects($this->once())
             ->method('getPayment')
             ->willReturn($payment);

        return $order;
    }

    /**
     * Create a payment
     *
     * @param bool $withAdditionalInformation
     * @return Payment
     */
    private function createPayment($withAdditionalInformation = true): Payment
    {
        $payment = $this->getMockBuilder(Payment::class)
            ->onlyMethods([
                'getId',
                'getAdditionalInformation'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $payment->expects($this->once())
            ->method('getId')
            ->willReturn(self::PAYMENT_ID);

        if (!$withAdditionalInformation) {
            return $payment;
        }

        $payment->expects($this->exactly(2))
            ->method('getAdditionalInformation')
            ->willReturnMap([
                ['paypal_order_id', self::PAYPAL_ORDER_ID],
                ['paypal_txn_id', self::PAYPAL_TRANSACTION_ID],
            ]);

        return $payment;
    }

    /**
     * Create an array of tracking information
     *
     * @return array
     */
    private function createTrackingInformation(): array
    {
        return [
            [
                'tracking_number' => 'number 1',
            ],
            [
                'tracking_number' => 'number 2',
            ],
        ];
    }
}
