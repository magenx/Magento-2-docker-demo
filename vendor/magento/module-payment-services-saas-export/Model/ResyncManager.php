<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesSaaSExport\Model;

use Exception;
use Magento\DataExporter\Model\FeedInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\FlagManager;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\SaaSCommon\Cron\SubmitFeedInterface;
use Magento\Framework\Indexer\ActionInterface as IndexerActionFeed;
use Magento\SaaSCommon\Model\ResyncManager as SaaSResyncManager;

/**
 * Manager for Payment Services feed re-sync functions
 */
class ResyncManager extends SaaSResyncManager
{
    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var SubmitFeedInterface
     */
    private $submitFeed;

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
    private $environment;

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
     * @param string $environment
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
        string $environment = ''
    ) {
        parent::__construct(
            $feedIndexer,
            $flagManager,
            $indexerRegistry,
            $submitFeed,
            $resourceConnection,
            $feedInterface,
            $flagName,
            $indexerName,
            $registryTableName
        );
        $this->flagManager = $flagManager;
        $this->submitFeed = $submitFeed;
        $this->feedInterface = $feedInterface;
        $this->flagName = $flagName;
        $this->environment = $environment;
    }

    /**
     * @inheritDoc
     */
    public function submitAllToFeed(): void
    {
        $lastSyncTimestamp = $this->flagManager->getFlagData($this->flagName);
        $data = $this->feedInterface->getFeedSince($lastSyncTimestamp ? $lastSyncTimestamp : '1');
        while ($data['recentTimestamp'] !== null) {
            $result = $this->submitFeed->submitFeed($data);
            if ($result) {
                $this->flagManager->saveFlag($this->flagName, $data['recentTimestamp']);
            } else {
                // phpcs:ignore Magento2.Exceptions.DirectThrow
                throw new Exception('There is an error during feed submit action.');
            }
            $lastSyncTimestamp = $this->flagManager->getFlagData($this->flagName);
            $data = $this->feedInterface->getFeedSince($lastSyncTimestamp ? $lastSyncTimestamp : '1');
        }
    }
}
