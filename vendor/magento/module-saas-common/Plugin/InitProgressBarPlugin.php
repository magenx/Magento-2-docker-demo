<?php
/*************************************************************************
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
 * ***********************************************************************
 */
declare(strict_types=1);

namespace Magento\SaaSCommon\Plugin;

use Magento\DataExporter\Model\Batch\BatchIteratorInterface;
use Magento\DataExporter\Model\Batch\BatchGeneratorInterface;
use Magento\DataExporter\Model\Batch\FeedSource\Generator;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\SaaSCommon\Console\ProgressBarManager;

/**
 * Initializes progress bar.
 */
class InitProgressBarPlugin
{
    /**
     * @param ProgressBarManager $progressBarManager
     */
    public function __construct(private ProgressBarManager $progressBarManager)
    {
    }

    /**
     * Initializes progress bar.
     *
     * @param Generator $subject
     * @param BatchIteratorInterface $result
     * @param FeedIndexMetadata $metadata
     * @param array $args
     * @return BatchIteratorInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGenerate(
        BatchGeneratorInterface $subject,
        BatchIteratorInterface $result,
        FeedIndexMetadata $metadata,
        array $args = []
    ): BatchIteratorInterface {
        if ($this->progressBarManager->isCliOutputEnabled() && $result->count() > 0) {
            $this->progressBarManager->start($result->count(), $metadata->getThreadCount());
        }

        return $result;
    }
}
