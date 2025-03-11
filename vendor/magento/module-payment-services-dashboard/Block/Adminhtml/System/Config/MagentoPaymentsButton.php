<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesDashboard\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * @api
 */
class MagentoPaymentsButton extends Field
{

    /**
     * Preparing global layout
     *
     * You can redefine this method in child classes for changing layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Magento_PaymentServicesDashboard::adminhtml/system/config/magento_payments_button.phtml');
        return $this;
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element = clone $element;
        $element->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Retrieve element HTML markup
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $buttonLabel = $originalData['button_label'];
        $this->addData(
            [
                'label' => __($buttonLabel),
                'html_id' => $element->getHtmlId(),
                'magento_payments_url' => $this->getUrl('paymentservicesdashboard/dashboard/index'),
                'magento_payments_settings_url' => $this->getUrl(
                    'paymentservicesdashboard/dashboard/index',
                    [
                        '_fragment' => 'settings'
                    ]
                ),
            ]
        );
        return $this->_toHtml();
    }
}
