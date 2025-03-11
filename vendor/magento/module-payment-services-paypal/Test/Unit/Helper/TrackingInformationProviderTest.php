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

namespace Magento\PaymentServicesPaypal\Test\Unit\Helper;

use Magento\PaymentServicesPaypal\Helper\PaypalApiDataFormatter;
use Magento\PaymentServicesPaypal\Helper\TrackingInformationProvider;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use PHPUnit\Framework\TestCase;

class TrackingInformationProviderTest extends TestCase
{
    private const PAYPAL_TRANSACTION = 'paypal_transaction_id';

    /**
     * @var TrackingInformationProvider
     */
    private $trackingInformationProvider;

    /**
     * @var PaypalApiDataFormatter
     */
    private $paypalApiDataFormatter;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->paypalApiDataFormatter = new(PaypalApiDataFormatter::class);
        $this->trackingInformationProvider = new TrackingInformationProvider($this->paypalApiDataFormatter);
    }

    /**
     * @return void
     */
    public function testGetTrackingInformation(): void
    {
        $shipment = $this->createShipment();

        $trackingInformation = $this->trackingInformationProvider->getTrackingInformation(
            $shipment,
            self::PAYPAL_TRANSACTION
        );

        $expected = [
            [
                TrackingInformationProvider::TRACKING_INFORMATION_WRAPPER => [
                    'capture_id' => self::PAYPAL_TRANSACTION,
                    'tracking_number' => 'number 1',
                    'carrier' => $this->paypalApiDataFormatter::CARRIER_OTHER,
                    'carrier_name_other' => 'carrier_code_1',
                    'notify_payer' => TrackingInformationProvider::NOTIFY_PAYER,
                    'items' => [
                        [
                            'name' => 'name 1',
                            'quantity' => 1,
                            'sku' => 'sku_1',
                            'upc' => [
                                'type' => PaypalApiDataFormatter::DEFAULT_UPC_TYPE,
                                'code' => '000001',
                            ],
                        ],
                        [
                            'name' => 'name 3',
                            'quantity' => 3,
                            'sku' => 'sku_3',
                            'upc' => [
                                'type' => PaypalApiDataFormatter::DEFAULT_UPC_TYPE,
                                'code' => '000003',
                            ],
                        ],
                    ]
                ]
            ],
            [
                TrackingInformationProvider::TRACKING_INFORMATION_WRAPPER => [
                    'capture_id' => self::PAYPAL_TRANSACTION,
                    'tracking_number' => 'number 2',
                    'carrier' => $this->paypalApiDataFormatter::CARRIER_OTHER,
                    'carrier_name_other' => 'carrier_code_2',
                    'notify_payer' => TrackingInformationProvider::NOTIFY_PAYER,
                    'items' => [
                        [
                            'name' => 'name 1',
                            'quantity' => 1,
                            'sku' => 'sku_1',
                            'upc' => [
                                'type' => PaypalApiDataFormatter::DEFAULT_UPC_TYPE,
                                'code' => '000001',
                            ],
                        ],
                        [
                            'name' => 'name 3',
                            'quantity' => 3,
                            'sku' => 'sku_3',
                            'upc' => [
                                'type' => PaypalApiDataFormatter::DEFAULT_UPC_TYPE,
                                'code' => '000003',
                            ],
                        ],
                    ]
                ]
            ],
        ];

        $this->assertEquals($expected, $trackingInformation);
    }

    /**
     * @return void
     */
    public function testGetTrackingInformationWithoutItems(): void
    {
        $shipment = $this->createShipmentWithoutItems();

        $trackingInformation = $this->trackingInformationProvider->getTrackingInformation(
            $shipment,
            self::PAYPAL_TRANSACTION
        );

        $this->assertEmpty($trackingInformation);
    }

    /**
     * @return void
     */
    public function testGetTrackingInformationWithoutTrackingInformation(): void
    {
        $shipment = $this->createShipmentWithoutTracks();

        $trackingInformation = $this->trackingInformationProvider->getTrackingInformation(
            $shipment,
            self::PAYPAL_TRANSACTION
        );

        $this->assertEmpty($trackingInformation);
    }

    /**
     * Create a shipment
     *
     * @return ShipmentInterface
     */
    private function createShipment(): ShipmentInterface
    {
        $shipment = $this->createMock(ShipmentInterface::class);

        $shipment->expects($this->once())
            ->method('getTracks')
            ->willReturn([
                $this->createShipmentTrack('1', 'carrier_code_1'),
                $this->createShipmentTrack('2', 'carrier_code_2'),
            ]);

        $shipment->expects($this->once())
            ->method('getItems')
            ->willReturn([
                $this->createShipmentItem('1', 1),
                $this->createShipmentItem('2', 0),
                $this->createShipmentItem('3', 3),
            ]);

        return $shipment;
    }

    /**
     * Create a shipment without items
     *
     * @return ShipmentInterface
     */
    private function createShipmentWithoutItems(): ShipmentInterface
    {
        $shipment = $this->createMock(ShipmentInterface::class);

        $shipment->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        $shipment->expects($this->never())
            ->method('getTracks');

        return $shipment;
    }

    /**
     * Create a shipment without tracks
     *
     * @return ShipmentInterface
     */
    private function createShipmentWithoutTracks(): ShipmentInterface
    {
        $shipment = $this->createMock(ShipmentInterface::class);

        $shipment->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->createShipmentItem('1', 1)]);

        $shipment->expects($this->once())
            ->method('getTracks')
            ->willReturn([]);

        return $shipment;
    }

    /**
     * Create a shipment item
     *
     * @param string $index
     * @param int $qty
     *
     * @return ShipmentItemInterface
     */
    private function createShipmentItem(string $index, int $qty): ShipmentItemInterface
    {
        $shipmentItem = $this->createMock(ShipmentItemInterface::class);

        $shipmentItem->expects($qty == 0 ? $this->never() : $this->once())
            ->method('getName')
            ->willReturn(sprintf('name %s', $index));

        $shipmentItem->expects($qty == 0 ? $this->never() : $this->once())
            ->method('getSku')
            ->willReturn(sprintf('sku_%s', $index));

        $shipmentItem->expects($qty == 0 ? $this->never() : $this->once())
            ->method('getProductId')
            ->willReturn($index);

        $shipmentItem->expects($this->any())
            ->method('getQty')
            ->willReturn($qty);

        return $shipmentItem;
    }

    /**
     * Create a shipment track
     *
     * @param string $index
     * @param string $code
     *
     * @return ShipmentTrackInterface
     */
    private function createShipmentTrack(string $index, string $code): ShipmentTrackInterface
    {
        $shipmentTrack = $this->createMock(ShipmentTrackInterface::class);

        $shipmentTrack->expects($this->once())
            ->method('getTrackNumber')
            ->willReturn(sprintf('number %s', $index));

        $shipmentTrack->expects($this->once())
            ->method('getCarrierCode')
            ->willReturn($code);

        return $shipmentTrack;
    }
}
