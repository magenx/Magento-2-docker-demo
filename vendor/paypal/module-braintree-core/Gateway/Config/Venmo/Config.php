<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Config\Venmo;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\CcConfig;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    /**
     * @var array
     */
    private array $icon = [];

    /**
     * @var CcConfig
     */
    private CcConfig $ccConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CcConfig $ccConfig
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CcConfig $ccConfig,
        string $methodCode = null,
        string $pathPattern = self::DEFAULT_PATH_PATTERN,
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->ccConfig = $ccConfig;
    }

    /**
     * Get Venmo icon
     *
     * @return array
     */
    public function getVenmoIcon(): array
    {
        if (empty($this->icon)) {
            $asset = $this->ccConfig->createAsset('PayPal_Braintree::images/venmo_logo_blue.png');
            list($width, $height) = getimagesizefromstring($asset->getSourceFile());
            $this->icon = [
                'url' => $asset->getUrl(),
                'width' => $width,
                'height' => $height
            ];
        }

        return $this->icon;
    }
}
