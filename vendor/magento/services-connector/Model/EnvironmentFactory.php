<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ServicesConnector\Model;

use InvalidArgumentException;
use Magento\Framework\ObjectManagerInterface;

/**
 * Factory for the Environment
 */
class EnvironmentFactory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param $environment
     * @throws InvalidArgumentException
     * @return Environment
     */
    public function create($environment)
    {
        if (!in_array($environment, ['production', 'sandbox'])) {
            throw new InvalidArgumentException("'$environment' environment is not valid");
        }
        return $this->objectManager->create(Environment::class, ['environment' => $environment]);
    }
}
