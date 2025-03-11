<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Plugin\Directory;

use Magento\Directory\Model\Region as AddressRegion;

class Region
{
    /**
     * @param AddressRegion $subject
     * @param string $name
     * @param string $countryId
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeLoadByName(AddressRegion $subject, $name, $countryId)
    {
        $subject->unsetData();
        return null;
    }
}
