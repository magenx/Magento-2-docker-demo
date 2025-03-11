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

namespace Magento\SaaSCommon\Console\Command;

use Magento\DataExporter\Model\FeedMetadataPool;
use Magento\DataExporter\Model\Indexer\ConfigOptionsHandler;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Console\Cli;
use Magento\SaaSCommon\Console\ProgressBarManager;
use Magento\SaaSCommon\Model\ResyncOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\SaaSCommon\Model\ResyncManager;
use Magento\SaaSCommon\Model\ResyncManagerPool;

/**
 * CLI command for Saas feed data resync
 */
class Resync extends Command
{
    /**
     * @var ResyncManagerPool
     */
    private $resyncManagerPool;

    /**
     * @var ResyncManager
     */
    private $resyncManager;

    /**
     * @var ResyncOptions
     */
    private ResyncOptions $resyncOptions;

    /**
     * @var ProgressBarManager
     */
    private ?ProgressBarManager $progressBarManager;

    /**
     * @var ConfigOptionsHandler
     */
    private ConfigOptionsHandler $configOptionsHandler;

    /**
     * @deprecated Left to keep backward compatibility.
     * @see Resync::$feedMetadataPool
     * Use $feedMetadataPool get FeedIndexMetadata instead of feedNames
     *
     * @var string[]
     */
    private $feedNames = [];

    /**
     * @var FeedMetadataPool
     */
    private FeedMetadataPool $feedMetadataPool;

    /**
     * @param ResyncManagerPool $resyncManagerPool
     * @param ResyncOptions $resyncOptions
     * @param string $name
     * @param FeedMetadataPool|null $feedMetadataPool
     * @param ProgressBarManager|null $progressBarManager
     * @param ConfigOptionsHandler|null $configOptionsHandler
     * @param array $feedNames
     */
    public function __construct(
        ResyncManagerPool $resyncManagerPool,
        ResyncOptions $resyncOptions,
        $name = '',
        ?FeedMetadataPool $feedMetadataPool = null,
        ?ProgressBarManager $progressBarManager = null,
        ?ConfigOptionsHandler $configOptionsHandler = null,
        array $feedNames = []
    ) {
        $this->resyncOptions = $resyncOptions;
        $this->resyncManagerPool = $resyncManagerPool;
        $this->feedMetadataPool = $feedMetadataPool ?? ObjectManager::getInstance()->get(FeedMetadataPool::class);
        $this->progressBarManager = $progressBarManager ?? ObjectManager::getInstance()->get(ProgressBarManager::class);
        $this->configOptionsHandler = $configOptionsHandler
            ?? ObjectManager::getInstance()->get(ConfigOptionsHandler::class);
        $this->feedNames = $feedNames;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Re-syncs feed data to SaaS service.');
        $this->addOption(
            ResyncOptions::FEED_OPTION,
            null,
            InputOption::VALUE_REQUIRED,
            'Feed name to fully re-sync to SaaS service. Available feeds: '
                . implode(', ', $this->getAllAvailableFeeds())
        );

        foreach ($this->resyncOptions->getOptionsList() as $optionConfig) {
            $this->addOption(
                $optionConfig['name'],
                null,
                $optionConfig['mode'] ?? null,
                $optionConfig['description'] ?? null
            );
        }

        parent::configure();
    }

    /**
     * Execute the command to re-sync SaaS data
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $feed = $this->resyncManagerPool->getActualFeedName((string)$input->getOption(ResyncOptions::FEED_OPTION));
        $availableFeeds = $this->getAllAvailableFeeds();

        foreach ($this->resyncOptions->getOptionsList() as $optionConfig) {
            if ($value = $input->getOption($optionConfig['name'])) {
                $this->resyncOptions->setOptionValue(
                    $optionConfig['name'],
                    $value
                );
            }
        }
        $this->configOptionsHandler->initialize($this->resyncOptions->getOptionValues());

        if (isset($availableFeeds[$feed])) {
            $this->progressBarManager->setOutput($output);
            $feedName = $availableFeeds[$feed];
            $this->resyncManager = $this->resyncManagerPool->getResyncManager($feed);
            if ($input->getOption(ResyncOptions::NO_REINDEX_OPTION)) {
                try {
                    $startTime = microtime(true);
                    $output->writeln('<info>Re-submitting ' . $feedName . ' feed data...</info>');
                    $this->resyncManager->executeResubmitOnly();
                    $time = $this->formatTime((int)(microtime(true) - $startTime));
                    $output->writeln('');
                    $output->writeln('<info>' . $feedName . ' feed data re-submit complete in ' . $time .'</info>');
                    $returnStatus = Cli::RETURN_SUCCESS;
                } catch (\Exception $ex) {
                    $output->writeln(
                        '<error>An error occurred re-submitting ' . $feedName . ' feed data to SaaS service.</error>'
                    );
                    $returnStatus = Cli::RETURN_FAILURE;
                }
            } else {
                try {
                    $startTime = microtime(true);
                    $output->writeln('<info>Executing full re-sync of ' . $feedName . ' feed data...</info>');
                    $this->resyncManager->executeFullResync();
                    $time = $this->formatTime((int)(microtime(true) - $startTime));
                    $output->writeln('');
                    $output->writeln('<info>' . $feedName . ' feed data full re-sync complete in ' . $time .'</info>');
                    $returnStatus = Cli::RETURN_SUCCESS;
                } catch (\Exception $ex) {
                    $output->writeln('<error>An error occurred re-syncing ' . $feedName
                        . ' feed data to SaaS service: ' . $ex->getMessage() .'.</error>');
                    $returnStatus = Cli::RETURN_FAILURE;
                }
            }
        } else {
            $output->writeln(
                '<error>Resync option --feed is required. Available feeds: '
                . implode(', ', array_keys($availableFeeds))
                . '</error>'
            );
            $returnStatus = Cli::RETURN_FAILURE;
        }

        return $returnStatus;
    }

    /**
     * Format time in seconds to days, hours, minutes and seconds
     *
     * @param int $seconds
     * @return string
     */
    private function formatTime(int $seconds): string
    {
        static $secondsInDay = 86400;
        $daysCount = 0;
        $seconds = $seconds - $secondsInDay;
        while ($seconds >= 0) {
            $daysCount++;
            $seconds = $seconds - $secondsInDay;
        }
        $seconds = $seconds + $secondsInDay;
        $time = gmdate('H:i:s', $seconds);
        return $daysCount > 0 ? sprintf('%s day(s) %s', $daysCount, $time) : $time;
    }

    /**
     * Get all available feeds by their metadata
     *
     * @return array
     */
    private function getAllAvailableFeeds(): array
    {
        $feeds = [];
        /** @var FeedIndexMetadata $feedMetadata */
        foreach ($this->feedMetadataPool->getAll() as $feedMetadata) {
            if ($this->resyncManagerPool->isResyncAvailable($feedMetadata->getFeedName())) {
                $feeds[$feedMetadata->getFeedName()] = $feedMetadata->getFeedSummary();
            }
        }
        // Keep backward compatibility with feeds which are not using FeedIndexMetadata
        foreach ($this->feedNames as $feedName => $feedSummary) {
            $feeds[$feedName] = $feedSummary;
        }

        return $feeds;
    }
}
