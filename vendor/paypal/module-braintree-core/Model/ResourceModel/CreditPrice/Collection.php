<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\ResourceModel\CreditPrice;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use PayPal\Braintree\Model\CreditPrice;
use PayPal\Braintree\Model\ResourceModel\CreditPrice as CreditPriceResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id'; //@codingStandardsIgnoreLine

    /**
     * Initialize resource collection
     *
     * @return void
     */
    protected function _construct(): void //@codingStandardsIgnoreLine
    {
        $this->_init(CreditPrice::class, CreditPriceResource::class);
    }
}
