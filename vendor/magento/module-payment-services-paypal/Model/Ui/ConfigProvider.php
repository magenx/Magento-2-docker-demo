<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public const CC_CODE = 'payment_services_paypal_hosted_fields';

    public const CC_VAULT_CODE = 'payment_services_paypal_vault';

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        $config = [];

        return $config;
    }
}
