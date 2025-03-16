<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQlServer\Model\Context;

use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

/**
 * Request context
 */
class Context implements ContextInterface
{
    /**
     * @var string[]
     */
    private $data;

    /**
     * @param array $data
     */
    public function __construct(
        array $data = []
    ) {
        $this->data = $data;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getValue(string $key): ?string
    {
        $result = null;
        if (isset($this->data[$key])) {
            $result = $this->data[$key];
        }
        return $result;
    }
}
