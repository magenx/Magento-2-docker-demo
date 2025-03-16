<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQlServer\Model\Context;

use Magento\Framework\ObjectManagerInterface;

/**
 * Context Factory
 */
class ContextFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var string
     */
    private $prototype;

    /**
     * @var ContextValueInterface[]
     */
    private $values;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param string $prototype
     * @param array $values
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        string $prototype = Context::class,
        array $values = []
    ) {
        $this->values = $values;
        $this->prototype = $prototype;
        $this->objectManager = $objectManager;
    }

    /**
     * @return array
     */
    public function create(): Context
    {
        $data = [];
        foreach ($this->values as $value) {
            $data[$value->getKey()] = $value->getValue();
        }
        return $this->objectManager->create($this->prototype, ['data' => $data]);
    }
}
