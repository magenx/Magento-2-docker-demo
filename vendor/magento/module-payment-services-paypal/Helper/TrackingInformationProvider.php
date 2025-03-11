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

namespace Magento\PaymentServicesPaypal\Helper;

use Magento\Sales\Api\Data\ShipmentInterface;

class TrackingInformationProvider
{
    public const NOTIFY_PAYER = false;
    public const TRACKING_INFORMATION_WRAPPER = 'create_order_tracking_information';

    /**
     * @var PaypalApiDataFormatter
     */
    private PaypalApiDataFormatter $paypalApiDataFormatter;

    /**
     * @param PaypalApiDataFormatter $paypalApiDataFormatter
     */
    public function __construct(PaypalApiDataFormatter $paypalApiDataFormatter)
    {
        $this->paypalApiDataFormatter = $paypalApiDataFormatter;
    }

    /**
     * Get Tracking Information
     *
     * @param ShipmentInterface $shipment
     * @param string $paypalTransactionId
     *
     * @return array
     */
    public function getTrackingInformation(ShipmentInterface $shipment, string $paypalTransactionId): array
    {
        $trackingInformation = [];

        $items = $this->getItems($shipment);
        if (empty($items)) {
            return [];
        }

        foreach ($shipment->getTracks() as $track) {
            $trackingInformation[] = [
                self::TRACKING_INFORMATION_WRAPPER => [
                    'capture_id' => $paypalTransactionId,
                    'tracking_number' => $track->getTrackNumber(),
                    'carrier' => $this->paypalApiDataFormatter::CARRIER_OTHER,
                    'carrier_name_other' =>  $track->getTitle() ?: $track->getCarrierCode(),
                    'notify_payer' => self::NOTIFY_PAYER,
                    'items' => $items,
                ]
            ];
        }

        return $trackingInformation;
    }

    /**
     * Get Shipment Items with qty > 0
     *
     * @param ShipmentInterface $shipment
     * @return array
     */
    private function getItems(ShipmentInterface $shipment): array
    {
        $items = [];

        foreach ($shipment->getItems() as $item) {
            if ($item->getQty() <= 0) {
                continue;
            }

            $items[] = [
                'name' => $this->paypalApiDataFormatter->formatName($item->getName()),
                'quantity' => $item->getQty(),
                'sku' => $this->paypalApiDataFormatter->formatSku($item->getSku()),
                'upc' => [
                    'type' => PaypalApiDataFormatter::DEFAULT_UPC_TYPE,
                    'code' => $this->paypalApiDataFormatter->formatUPCCode((int)$item->getProductId()),
                ],
            ];
        }

        return $items;
    }
}
