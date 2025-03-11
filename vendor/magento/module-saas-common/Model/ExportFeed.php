<?php
/**
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

namespace Magento\SaaSCommon\Model;

use Magento\DataExporter\Model\ExportFeedInterface;
use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\Event\ManagerInterface;
use Magento\SaaSCommon\Model\Http\Command\SubmitFeed;

class ExportFeed implements ExportFeedInterface
{
    private const SENDER = 'Commerce Data Exporter';
    /**
     * @var SubmitFeed
     */
    private SubmitFeed $submitFeed;
    private ManagerInterface $eventManager;
    private DataFilter $dataFilter;

    /**
     * @param SubmitFeed $submitFeed
     * @param ManagerInterface $eventManager
     * @param DataFilter $dataFilter
     */
    public function __construct(
        SubmitFeed $submitFeed,
        ManagerInterface $eventManager,
        DataFilter $dataFilter,
    ) {
        $this->submitFeed = $submitFeed;
        $this->eventManager = $eventManager;
        $this->dataFilter = $dataFilter;
    }

    /**
     * {@inheirtDoc}
     *
     * @param array $data
     * @param FeedIndexMetadata $metadata
     * @return FeedExportStatus
     */
    public function export(array $data, FeedIndexMetadata $metadata): FeedExportStatus
    {
        $data = $this->dataFilter->filter($metadata->getFeedName(), $data);
        $status = $this->submitFeed->execute($metadata->getFeedName(), $data);
        $this->sendNotification($status, $metadata, $data);
        return $status;
    }

    /**
     * Trigger an event to notify the system that the data has been transmitted externally.
     *
     * @param FeedExportStatus $exportStatus
     * @param FeedIndexMetadata $metadata
     * @param array $data
     * @return void
     */
    private function sendNotification(FeedExportStatus $exportStatus, FeedIndexMetadata $metadata, array $data): void
    {
        $map = $this->map($metadata);
        if ($map && $exportStatus->getStatus()->isSent()) {
            $this->eventManager->dispatch(
                "data_sent_outside",
                [
                    "sender" => self::SENDER,
                    "destination" => $map['destination'],
                    "timestamp" => time(),
                    "type" => $map['type'],
                    "data" => $data
                ]
            );
        }
    }

    /**
     * Map feed name to destination and type
     *
     * @param FeedIndexMetadata $metadata
     * @return array
     */
    private function map(FeedIndexMetadata $metadata): array
    {
        return match ($metadata->getFeedName()) {
            "orders" => [
                'type' => "sales",
                'destination' => "comdomainsvc-order-service",
            ],
            default => [
                'type' => $metadata->getFeedName(),
                'destination' => 'feed-service'
            ],
        };
    }

}
