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

/**
 * Class responsible for resync options handling
 */
class ResyncOptions
{
    /**
     * List of available resync options
     */
    public const NO_REINDEX_OPTION = 'no-reindex';
    public const CLEANUP_FEED = 'cleanup-feed';
    public const FEED_OPTION = 'feed';
    public const DRY_RUN_OPTION = 'dry-run';
    public const THREAD_COUNT = 'thread-count';
    public const BATCH_SIZE = 'batch-size';
    public const CONTINUE_RESYNC = 'continue-resync';

    /**
     * List of options
     *
     * @var array
     */
    private array $optionsList;

    /**
     * List of options with their values
     * @var array
     */
    private array $optionValues;

    /**
     * @param array $optionsList
     * @param array $optionValues
     */
    public function __construct(
        array $optionsList = [],
        array $optionValues = []
    ) {
        $this->optionsList = $optionsList;
        $this->optionValues = $optionValues;
    }

    /**
     * Set specific resync option value
     *
     * @param string $optionName
     * @param mixed $value
     * @return void
     */
    public function setOptionValue(string $optionName, mixed $value): void
    {
        $this->optionValues[$optionName] = $value;
    }

    /**
     * Get list of resync option values
     *
     * @return array
     */
    public function getOptionValues(): array
    {
        return $this->optionValues;
    }

    /**
     * Get list of all resync options
     *
     * @return array
     */
    public function getOptionsList(): array
    {
        return $this->optionsList;
    }
}
