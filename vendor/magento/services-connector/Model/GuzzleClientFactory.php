<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ServicesConnector\Model;

use Magento\Framework\ObjectManagerInterface;

/**
 * Factory for the \GuzzleHttp\Client object creation
 */
class GuzzleClientFactory
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
     * @param array $data
     * @return \GuzzleHttp\Client
     */
    public function create(array $data = [])
    {
        return $this->objectManager->create(\GuzzleHttp\Client::class, ['config' => $data]);
    }
}
