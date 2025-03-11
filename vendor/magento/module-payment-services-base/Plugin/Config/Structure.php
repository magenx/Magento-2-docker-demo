<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Plugin\Config;

use Magento\Config\Model\Config\Structure as ConfigStructure;

class Structure
{
    /**
     * @var string[]
     */
    private $sections = [
        'payment_us_recommended_solutions_magento_payments',
        'payment_us_recommended_solutions_magento_payments_general_configuration',
    ];

    /**
     * Structure section list.
     *
     * @param ConfigStructure $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionList(ConfigStructure $subject, array $result) : array
    {
        foreach ($this->sections as $section) {
            $result[$section] = true;
        }
        return $result;
    }
}
