<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Block\GooglePay\Shortcut;

use PayPal\Braintree\Block\GooglePay\AbstractButton;
use Magento\Catalog\Block\ShortcutInterface;

class Button extends AbstractButton implements ShortcutInterface
{
    private const ALIAS_ELEMENT_INDEX = 'alias';
    private const BUTTON_ELEMENT_INDEX = 'button_id';

    /**
     * @inheritdoc
     */
    public function getAlias()
    {
        return $this->getData(self::ALIAS_ELEMENT_INDEX);
    }

    /**
     * Get container id
     *
     * @return string
     */
    public function getContainerId(): string
    {
        return $this->getData(self::BUTTON_ELEMENT_INDEX);
    }

    /**
     * Get extra class name
     *
     * @return string
     */
    public function getExtraClassname(): string
    {
        return $this->getIsCart() ? 'cart' : 'minicart';
    }
}
