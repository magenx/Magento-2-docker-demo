<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Model\ServicesConnector;

use Magento\ServicesConnector\Api\KeyValidationInterface;

class InMemoryKeyValidor implements KeyValidationInterface
{
    /**
     * @var array
     */
    private array $inMemoryCache = [];

    /**
     * @var KeyValidationInterface
     */
    private KeyValidationInterface $backendKeyValidator;

    /**
     * @param KeyValidationInterface $backendKeyValidator
     */
    public function __construct(KeyValidationInterface $backendKeyValidator)
    {
        $this->backendKeyValidator = $backendKeyValidator;
    }

    /**
     * @inheritdoc
     */
    public function execute($extension, $environment = 'production'): bool
    {
        if ($this->isInCache($extension, $environment)) {
            return $this->getFromCache($extension, $environment);
        }

        return $this->validateAndCache($extension, $environment);
    }

    /**
     * Check if the validation result is already in the cache
     *
     * @param string $extension
     * @param string $environment
     * @return bool
     */
    private function isInCache($extension, $environment): bool
    {
        return isset($this->inMemoryCache[$extension][$environment]);
    }

    /**
     * Retrieve the result of the validation from the cache
     *
     * @param string $extension
     * @param string $environment
     * @return bool
     */
    private function getFromCache($extension, $environment): bool
    {
        return $this->inMemoryCache[$extension][$environment];
    }

    /**
     * Store the result of the validation to the cache
     *
     * @param string $extension
     * @param string $environment
     * @return bool
     * @throws \Magento\ServicesConnector\Exception\KeyNotFoundException
     * @throws \Magento\ServicesConnector\Exception\PrivateKeySignException
     */
    private function validateAndCache($extension, $environment): bool
    {
        $isValid = $this->backendKeyValidator->execute($extension, $environment);
        $this->inMemoryCache[$extension][$environment] = $isValid;
        return $isValid;
    }
}
