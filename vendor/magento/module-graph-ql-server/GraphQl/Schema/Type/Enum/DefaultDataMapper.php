<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQlServer\GraphQl\Schema\Type\Enum;

/**
 * Default data mapper for GraphQl
 */
class DefaultDataMapper extends \Magento\Framework\GraphQl\Schema\Type\Enum\DefaultDataMapper
{
    /**
     * @param array $map
     */
    public function __construct(array $map = [])
    {
        parent::__construct($map);
    }
}
