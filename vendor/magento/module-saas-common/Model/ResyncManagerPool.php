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

use Magento\Framework\ObjectManagerInterface;

/**
 * Pool of all feed resync managers
 */
class ResyncManagerPool
{
    /**
     * @var array
     */
    private $registry;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $classMap;

    /**
     * Link deprecated feed names with new ones
     *
     * @var string[]
     */
    private $feedNamesMapping = [];

    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $classMap
     * @param array $feedNamesMapping
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $classMap = [],
        array $feedNamesMapping = []
    ) {
        $this->objectManager = $objectManager;
        $this->classMap = $classMap;
        $this->feedNamesMapping = $feedNamesMapping;
    }

    /**
     * Returns resync manager object
     *
     * @param string $feedName
     * @return ResyncManager
     * @throws \InvalidArgumentException
     */
    public function getResyncManager(string $feedName) : ResyncManager
    {
        // Keep backward compatibility with feeds which were renamed
        $feedName = $this->getActualFeedName($feedName);
        if (!$this->isResyncAvailable($feedName)) {
            $options = implode(',', array_keys($this->classMap));
            throw new \InvalidArgumentException('Resync feed option is not available. Available feeds: ' . $options);
        }
        if (!isset($this->registry[$feedName])) {
            $this->registry[$feedName] = $this->objectManager->get($this->classMap[$feedName]);
        }
        return $this->registry[$feedName];
    }

    /**
     * Check if resync operation is available for feed
     *
     * @param string $feedName
     * @return bool
     */
    public function isResyncAvailable(string $feedName): bool
    {
        $feedName = $this->getActualFeedName($feedName);
        return isset($this->classMap[$feedName]);
    }

    /**
     * Get feed name from the feed names mapping
     *
     * Needed to keep backward compatibility with feeds which were renamed
     *
     * @param string $feedName
     * @return string
     */
    public function getActualFeedName(string $feedName): string
    {
        return $this->feedNamesMapping[$feedName] ?? $feedName;
    }
}
