<?php
/**
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

namespace Magento\SaaSCommon\Test\Integration;

use Magento\DataExporter\Model\ExportFeedInterface;
use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Status\ExportStatusCode;
use Magento\TestFramework\Helper\Bootstrap;

class ExportFeedStub implements ExportFeedInterface
{
    /**
     * Export data
     *
     * @param array $data
     * @param FeedIndexMetadata $metadata
     * @return FeedExportStatus
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function export(array $data, FeedIndexMetadata $metadata): FeedExportStatus
    {

        $statusCode = Bootstrap::getObjectManager()->create(ExportStatusCode::class, ['statusCode' => 200]);
        return Bootstrap::getObjectManager()->create(
            FeedExportStatus::class,
            [
                'status' => $statusCode,
                'reasonPhrase' => '',
                'failedItems' => []
            ]
        );
    }
}
