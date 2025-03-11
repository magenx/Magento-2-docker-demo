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

use Exception;
use Magento\DataExporter\Model\Batch\BatchGeneratorInterface;
use Magento\DataExporter\Model\Batch\Feed\Generator as FeedBatchGenerator;
use Magento\DataExporter\Model\FeedInterface;
use Magento\DataExporter\Model\Indexer\Config as IndexerConfig;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\FlagManager;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\SaaSCommon\Cron\SubmitFeedInterface;
use Magento\SaaSCommon\Model\Exception\UnableSendData;
use Magento\Framework\Indexer\ActionInterface as IndexerActionFeed;
use Magento\Indexer\Model\ProcessManagerFactory;
use Magento\DataExporter\Lock\FeedLockManager;

/**
 * Manager for SaaS feed re-sync functions
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResyncManager
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var SubmitFeedInterface
     */
    private SubmitFeedInterface $submitFeed;

    /**
     * @var FeedInterface
     */
    private $feedInterface;

    /**
     * @var string
     */
    private $flagName;

    /**
     * @var string
     */
    private $indexerName;

    /**
     * @var string
     */
    private $registryTableName;

    /**
     * @var ProcessManagerFactory
     */
    private ProcessManagerFactory $processManagerFactory;

    /**
     * @var BatchGeneratorInterface
     */
    private BatchGeneratorInterface $batchGenerator;

    /**
     * @var FeedLockManager
     */
    private FeedLockManager $feedLockManager;

    /**
     * @var IndexerConfig
     */
    private IndexerConfig $indexerConfig;

    /**
     * @var CommerceDataExportLoggerInterface|null
     */
    private ?CommerceDataExportLoggerInterface $logger;

    /**
     * @param IndexerActionFeed $feedIndexer
     * @param FlagManager $flagManager
     * @param IndexerRegistry $indexerRegistry
     * @param SubmitFeedInterface $submitFeed
     * @param ResourceConnection $resourceConnection
     * @param FeedInterface $feedInterface
     * @param string $flagName
     * @param string $indexerName
     * @param string $registryTableName
     * @param BatchGeneratorInterface|null $batchGenerator
     * @param ProcessManagerFactory|null $processManagerFactory
     * @param FeedLockManager|null $feedLockManager
     * @param IndexerConfig|null $indexerConfig
     * @param CommerceDataExportLoggerInterface|null $logger
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        IndexerActionFeed $feedIndexer,
        FlagManager $flagManager,
        IndexerRegistry $indexerRegistry,
        SubmitFeedInterface $submitFeed,
        ResourceConnection $resourceConnection,
        FeedInterface $feedInterface,
        string $flagName,
        string $indexerName,
        string $registryTableName,
        ?BatchGeneratorInterface $batchGenerator = null,
        ?ProcessManagerFactory $processManagerFactory = null,
        ?FeedLockManager $feedLockManager = null,
        ?IndexerConfig $indexerConfig = null,
        ?CommerceDataExportLoggerInterface $logger = null
    ) {
        $this->flagManager = $flagManager;
        $this->indexerRegistry = $indexerRegistry;
        $this->submitFeed = $submitFeed;
        $this->resourceConnection = $resourceConnection;
        $this->feedInterface = $feedInterface;
        $this->flagName = $flagName;
        $this->indexerName = $indexerName;
        $this->registryTableName = $registryTableName;
        $this->batchGenerator = $batchGenerator ??
            ObjectManager::getInstance()->get(FeedBatchGenerator::class);
        $this->processManagerFactory = $processManagerFactory ??
            ObjectManager::getInstance()->get(ProcessManagerFactory::class);
        $this->feedLockManager = $feedLockManager ??
            ObjectManager::getInstance()->get(FeedLockManager::class);
        $this->indexerConfig = $indexerConfig ??
            ObjectManager::getInstance()->get(IndexerConfig::class);
        $this->logger = $logger ??
            ObjectManager::getInstance()->get(CommerceDataExportLoggerInterface::class);
    }

    /**
     * Execute full SaaS feed data re-generate and re-submit
     *
     * @throws \Zend_Db_Statement_Exception
     * @throws UnableSendData
     */
    public function executeFullResync(): void
    {
        $this->checkLock(function () {
            $this->ensureNoRunningIndexer();
            if ($this->indexerConfig->isCleanUpFeed()) {
                $this->truncateIndexTable();
            }
            if ($this->isImmediateExport()) {
                $this->regenerateFeedData();
                return;
            }
            $this->resetSubmittedData();
            $this->regenerateFeedData();
            $this->submitAllToFeed();
        });
    }

    /**
     * Execute SaaS feed data re-submit only
     *
     * @throws \Zend_Db_Statement_Exception
     */
    public function executeResubmitOnly(): void
    {
        $this->checkLock(function () {
            $this->ensureNoRunningIndexer();
            if ($this->isImmediateExport()) {
                // index will not be truncated
                $this->regenerateFeedData();
                $this->submitAllToFeed();
            } else {
                $this->resetSubmittedData();
                $this->submitAllToFeed();
            }
        });
    }

    /**
     * Reset SaaS indexed feed data in order to re-generate
     * @deprecated
     * @phpcs:disable Magento2.Functions.DiscouragedFunction
     * @throws \Zend_Db_Statement_Exception
     */
    public function resetIndexedData(): void
    {
        $indexer = $this->indexerRegistry->get($this->indexerName);
        $indexer->invalidate();
    }

    /**
     * Reset SaaS submitted feed data in order to re-send
     *
     * @throws \Zend_Db_Statement_Exception
     */
    public function resetSubmittedData(): void
    {
        if ($this->isImmediateExport()) {
            return ;
        }
        $connection = $this->resourceConnection->getConnection();
        $registryTable = $this->resourceConnection->getTableName($this->registryTableName);
        $connection->truncateTable($registryTable);
        $this->flagManager->deleteFlag($this->flagName);
    }

    /**
     * Re-index SaaS feed data
     *
     * @throws \Zend_Db_Statement_Exception
     */
    public function regenerateFeedData(): void
    {
        $indexer = $this->indexerRegistry->get($this->indexerName);
        $indexer->reindexAll();
    }

    /**
     * @return void
     */
    private function ensureNoRunningIndexer(): void
    {
        if ($this->indexerRegistry->get($this->indexerName)->isWorking()) {
            throw new \RuntimeException(sprintf(
                'Feed sync skipped, indexer "%1$s" is in progress. You can check status with "bin/magento indexer:status %1$s"',
                $this->indexerName
            ));
        }
    }

    /**
     * Regenerate feed data by ids
     *
     * @param array $ids
     * @return void
     */
    public function regenerateFeedDataByIds(array $ids): void
    {
        $indexer = $this->indexerRegistry->get($this->indexerName);
        $indexer->reindexList($ids);
    }

    /**
     * Truncates feed index table
     */
    public function truncateIndexTable(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $metadata = $this->feedInterface->getFeedMetadata();
        $feedTable = $this->resourceConnection->getTableName($metadata->getFeedTableName());
        $connection->truncateTable($feedTable);
    }

    /**
     * Submit all items to feed
     *
     * @throws \Zend_Db_Statement_Exception
     * @throws UnableSendData
     * @throws Exception
     */
    public function submitAllToFeed(): void
    {
        // TODO: Remove when all feeds are moved to immediate export
        if ($this->isImmediateExport()) {
            // data already submitted right after collecting. @see \Magento\SaaSCommon\Model\ExportFeed::export
            return;
        }

        $lastSyncTimestamp = $this->flagManager->getFlagData($this->flagName) ?? '1';
        $metadata = $this->feedInterface->getFeedMetadata();

        $this->logger->initSyncLog($metadata, 'full sync(legacy)');

        $batchIterator = $this->batchGenerator->generate($metadata, ['sinceTimestamp' => $lastSyncTimestamp]);
        $threadCount = min($metadata->getThreadCount(), $batchIterator->count());
        $userFunctions = [];
        $dateTimeFormat = $metadata->getDbDateTimeFormat();
        for ($threadNumber = 1; $threadNumber <= $threadCount; $threadNumber++) {
            $userFunctions[] = function () use ($batchIterator, $dateTimeFormat) {
                // phpcs:disable Generic.Formatting.DisallowMultipleStatements.SameLine
                // phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall
                for ($batchIterator->rewind(); $batchIterator->valid(); $batchIterator->next()) {
                    $data = $batchIterator->current();
                    $result = $this->submitFeed->submitFeed($data);
                    if ($result) {
                        $this->flagManager->saveFlag(
                            $this->flagName,
                            (new \DateTime())->format($dateTimeFormat)
                        );
                    } else {
                        $batchIterator->markBatchForRetry();
                    }
                }
                // phpcs:enable Generic.Formatting.DisallowMultipleStatements.SameLine
            };
        }

        $processManager = $this->processManagerFactory->create(['threadsCount' => $threadCount]);
        $processManager->execute($userFunctions);
        if ($batchIterator->count() > 0) {
            $this->logger->complete();
        }
    }

    /**
     * Check is immediate export
     *
     * @return bool
     */
    private function isImmediateExport(): bool
    {
        return $this->feedInterface->getFeedMetadata()->isExportImmediately();
    }

    /**
     * Check feed lock and execute callback
     *
     * @param callable $userFunction
     *
     * @return void
     */
    private function checkLock(callable $userFunction)
    {
        $feedName = $this->feedInterface->getFeedMetadata()->getFeedName();
        if ($this->feedLockManager->isLocked($feedName) || !$this->feedLockManager->lock($feedName, 'resync')) {
            $lockedBy = $this->feedLockManager->getLockedByName($feedName);
            throw new \RuntimeException('Feed sync skipped, resource occupied by another process: ' . $lockedBy);
        }

        try {
            $userFunction();
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('There is an error during feed submit action:' . $e->getMessage());
        } finally {
            $this->feedLockManager->unlock($feedName);
        }
    }
}
