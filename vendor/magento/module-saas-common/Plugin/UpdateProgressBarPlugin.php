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
use Magento\SaaSCommon\Console\ProgressBarManager;

class UpdateProgressBarPlugin
{
    /**
     * @param ProgressBarManager $progressBarManager
     */
    public function __construct(private ProgressBarManager $progressBarManager)
    {
    }

    /**
     * Updates progress bar.
     *
     * @param BatchIteratorInterface $subject
     * @param array $result
     * @return array
     */
    public function afterCurrent(BatchIteratorInterface $subject, array $result): array
    {
        if ($this->progressBarManager->isCliOutputEnabled()) {
            $this->progressBarManager->setProgress($subject->key());
        }

        return $result;
    }

    /**
     * Updates progress bar.
     *
     * @param BatchIteratorInterface $subject
     * @param bool $result
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterValid(BatchIteratorInterface $subject, bool $result): bool
    {
        if ($result === false && $this->progressBarManager->isCliOutputEnabled()) {
            $this->progressBarManager->finish();
        }

        return $result;
    }
}
