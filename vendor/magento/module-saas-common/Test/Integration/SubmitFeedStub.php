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

namespace Magento\SaaSCommon\Test\Integration;

use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\FeedExportStatusBuilder;
use Magento\SaaSCommon\Model\Http\Command\SubmitFeed;
use Magento\TestFramework\Helper\Bootstrap;

class SubmitFeedStub extends SubmitFeed
{
    /**
     * Export data
     *
     * @param string $feedName
     * @param array $data
     * @param int|null $timeout
     * @return FeedExportStatus
     */
    public function execute(string $feedName, array $data, ?int $timeout = null) : FeedExportStatus
    {

        $feedExportStatusBuilder = Bootstrap::getObjectManager()->create(FeedExportStatusBuilder::class);
        return $feedExportStatusBuilder->build(
            200,
            'OK',
            []
        );
    }
}
