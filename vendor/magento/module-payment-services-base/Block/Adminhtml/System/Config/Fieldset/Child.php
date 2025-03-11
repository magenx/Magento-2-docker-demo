<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Block\Adminhtml\System\Config\Fieldset;

/**
 * @api
 */
class Child extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var bool
     */
    protected $isCollapsedDefault = true;

    /**
     * Collapsed or expanded fieldset when page loaded?
     *
     * @param AbstractElement $element
     * @return bool
     */
    protected function _isCollapseState($element)
    {
        $extra = $this->_authSession->getUser()->getExtra();
        if (isset($extra['configState']) && isset($extra['configState'][$element->getId()])) {
            return $extra['configState'][$element->getId()];
        }
        return $this->isCollapsedDefault;
    }
}
