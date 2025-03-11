<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Plugin\Config;

use Magento\Config\Model\Config\Structure as ConfigStructure;

class Structure
{
    /**
     * @var string[]
     */
    private $sections = [
        'payment_us_recommended_solutions_magento_payments_hosted_fields',
        'payment_us_recommended_solutions_magento_payments_smart_buttons',
        'payment_us_recommended_solutions_magento_payments_apple_pay',
        'payment_us_recommended_solutions_magento_payments_google_pay',
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
