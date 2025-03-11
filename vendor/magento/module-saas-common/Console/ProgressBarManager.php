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

namespace Magento\SaaSCommon\Console;

use Magento\Framework\App\CacheInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Display progress bar in CLI
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class ProgressBarManager
{
    private ?ConsoleOutputInterface $output = null;
    private ProgressBar $progressBar;
    private bool $isMasterThread = false;
    private int $maxStep = 0;
    private int $step = 0;

    private ?int $threadId;
    private int $threadCount = 1;
    private int $threadSubmitIteration = 0;
    private int $threadStepIteration = 0;
    private int $threadSubmitMultiplier = 0;
    private int $threadStepMultiplier = 1;
    private int $threadSubmittedItemsCount = 0;
    private int $threadFailedItemsCount = 0;
    private int $submittedItemsTotal = 0;
    private int $failedItemsTotal = 0;
    private ?string $cacheKey;
    private int $dataProcessingPeriod;
    private bool $isProcessingOfRemainingFeedItems = false;

    /**
     * @param CacheInterface $cache
     */
    public function __construct(private CacheInterface $cache)
    {
    }

    /**
     * Sets CLI output.
     *
     * @param ConsoleOutputInterface $output
     */
    public function setOutput(ConsoleOutputInterface  $output): void
    {
        $this->output = $output;
    }

    /**
     * Checks if CLI output is enabled.
     *
     * @return bool
     */
    public function isCliOutputEnabled(): bool
    {
        return $this->output !== null;
    }

    /**
     * Starts progress bar.
     *
     * @param int $total
     * @param int $threadCount
     * @return void
     */
    public function start(int $total, int $threadCount): void
    {
        if ($this->output === null) {
            throw new \InvalidArgumentException('Output is not set');
        }

        $this->cacheKey = hash('sha256', microtime()) . '_progress_bar_state';
        $this->cleanUp();
        $this->threadCount = $threadCount;
        $this->maxStep = $total;
        $threadStepsCount = (int)($this->maxStep / $this->threadCount);
        $this->dataProcessingPeriod = $this->getThreadSyncPeriod($threadStepsCount);

        $this->progressBar = new ProgressBar($this->output, $this->maxStep);
        $this->progressBar->setFormat(
            '%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %submitted% %failed%'
        );
        $this->progressBar->setMessage('', 'submitted');
        $this->progressBar->setMessage('', 'failed');

        if ('\\' !== \DIRECTORY_SEPARATOR) {
            $this->progressBar->setEmptyBarCharacter('░'); // light shade character \u2591
            $this->progressBar->setProgressCharacter('');
            $this->progressBar->setBarCharacter('▓'); // dark shade character \u2593
        }
        $this->progressBar->setOverwrite(true);
        $this->progressBar->start();
    }

    /**
     * Cleans up progress bar state.
     *
     * @return void
     */
    private function cleanUp(): void
    {
        $this->cache->remove($this->cacheKey);
        $this->threadId = null;
        $this->threadSubmitIteration = 0;
        $this->threadSubmitMultiplier = 0;
        $this->threadStepIteration = 0;
        $this->threadStepMultiplier = 1;
        $this->threadSubmittedItemsCount = 0;
        $this->threadFailedItemsCount = 0;
        $this->submittedItemsTotal = 0;
        $this->failedItemsTotal = 0;
        $this->isProcessingOfRemainingFeedItems = false;
    }

    /**
     * Sets progress bar step.
     *
     * @param int $step
     * @return void
     */
    public function setProgress(int $step): void
    {
        $this->step = $step;
        $this->threadStepIteration++;
        if ($this->step === 1) {
            $this->isMasterThread = true;
        }

        if ($this->threadId === null) {
            $this->threadId = $step;
        }

        if (!$this->isMasterThread) {
            return;
        }

        if ($this->threadSubmitIteration
            && $this->threadStepIteration === $this->dataProcessingPeriod * $this->threadStepMultiplier
        ) {
            $this->loadExportProgress();
            $this->threadStepMultiplier++;
        }

        if ($this->submittedItemsTotal || $this->failedItemsTotal) {
            $this->progressBar->setMessage("Submitted: ~" . $this->submittedItemsTotal, 'submitted');
            $this->progressBar->setMessage("Failed: ~" . $this->failedItemsTotal, 'failed');
        }
        $this->progressBar->setProgress($step);
    }

    /**
     * Updates progress bar with info about exported items.
     *
     * @param int $submittedCount
     * @param int $failedCount
     * @param bool $isSuccess
     * @return void
     */
    public function updateExportInfo(int $submittedCount, int $failedCount, bool $isSuccess = true): void
    {
        if (!$this->step) {
            return;
        }

        $this->threadSubmitIteration++;
        $this->threadSubmittedItemsCount += !$isSuccess && empty($failedCount) ? 0 : $submittedCount;
        $this->threadFailedItemsCount += !$isSuccess && empty($failedCount) ? $submittedCount : $failedCount;
        $threadProgressSyncIteration = $this->threadId + $this->dataProcessingPeriod * $this->threadSubmitMultiplier;

        if ($this->threadSubmitIteration === $threadProgressSyncIteration
            || $this->isProcessingOfRemainingFeedItems
            || $this->threadCount === 1) {
            $this->syncExportProgress();
            $this->threadSubmitMultiplier++;
        }

        if ($this->isMasterThread || $this->isProcessingOfRemainingFeedItems) {
            $this->progressBar->setMessage("Submitted: ~" . $this->submittedItemsTotal, 'submitted');
            $this->progressBar->setMessage("Failed: ~" . $this->failedItemsTotal, 'failed');
            $this->progressBar->display();
        }
    }

    /**
     * Executes threads final steps of progress bar.
     *
     * @return void
     */
    public function finish(): void
    {
        // small random delay to prevent race conditions on final iterations
        usleep(rand(1000000, 3000000));
        $this->syncExportProgress();
        if ($this->submittedItemsTotal || $this->failedItemsTotal) {
            $this->progressBar->setMessage("Submitted: ~" . $this->submittedItemsTotal, 'submitted');
            $this->progressBar->setMessage("Failed: ~" . $this->failedItemsTotal, 'failed');

        }
        $this->progressBar->display();
        $this->progressBar->finish();
        $this->isProcessingOfRemainingFeedItems = true;
    }

    /**
     * Syncs export progress between threads.
     *
     * @return void
     */
    private function syncExportProgress(): void
    {
        if ($this->threadCount === 1) {
            $this->submittedItemsTotal = $this->threadSubmittedItemsCount;
            $this->failedItemsTotal = $this->threadFailedItemsCount;
            return;
        }

        $result = $this->cache->load($this->cacheKey);

        $exportInfo = $result ? json_decode($result, true) : [0,0];
        $exportInfo[0] += $this->threadSubmittedItemsCount;
        $exportInfo[1] += $this->threadFailedItemsCount;
        $this->cache->save(json_encode($exportInfo), $this->cacheKey, [], 86400);

        $this->submittedItemsTotal = $exportInfo[0];
        $this->failedItemsTotal = $exportInfo[1];
        $this->threadSubmittedItemsCount = 0;
        $this->threadFailedItemsCount = 0;
    }

    /**
     * Loads export progress.
     *
     * @return void
     */
    private function loadExportProgress(): void
    {
        if ($this->threadCount === 1) {
            return;
        }

        $result = $this->cache->load($this->cacheKey);
        $exportInfo = $result ? json_decode($result, true) : [0,0];
        $this->submittedItemsTotal = $exportInfo[0];
        $this->failedItemsTotal = $exportInfo[1];
    }

    /**
     * Returns threads sync period.
     *
     * Used to sync progress bar between threads.
     *
     * @param int $stepsPerThread
     * @return int
     */
    private function getThreadSyncPeriod(int $stepsPerThread): int
    {
        return match (true) {
            $stepsPerThread < 100 => 5,
            $stepsPerThread < 1000 => 10,
            $stepsPerThread < 10000 => 100,
            default => 200,
        };
    }
}
