<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ResetPaymentsMerchantIdButton extends Field
{
    private const RESET_SANDBOX_MERCHANT_ID = 'reset_sandbox_merchant_id';
    private const RESET_PRODUCTION_MERCHANT_ID = 'reset_production_merchant_id';

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
        $this->setTemplate(
            'Magento_PaymentServicesBase::adminhtml/system/config/reset_payments_merchant_id_button.phtml'
        );
        return $this;
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $config = '';
        $buttonId = $element->getOriginalData()['id'];

        if ($buttonId == self::RESET_SANDBOX_MERCHANT_ID) {
            $config = $this->_scopeConfig->getValue('payment/payment_methods/sandbox_merchant_id');
        }
        if ($buttonId == self::RESET_PRODUCTION_MERCHANT_ID) {
            $config = $this->_scopeConfig->getValue('payment/payment_methods/production_merchant_id');
        }
        if (empty($config)) {
            return '';
        }

        $element->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $buttonLabel = $originalData['button_label'];
        $payEnvironment = '';

        if ($originalData['id'] == self::RESET_SANDBOX_MERCHANT_ID) {
            $payEnvironment = 'sandbox';
        } elseif ($originalData['id'] == self::RESET_PRODUCTION_MERCHANT_ID) {
            $payEnvironment = 'production';
        }

        $controllerUrl = $this->getUrl('paymentservicesbase/system_config/ResetPaymentsMerchantId') .
            '?environment' . "=" . $payEnvironment;
        $this->addData(
            [
                'label' => __($buttonLabel),
                'html_id' => $element->getHtmlId(),
                'delete_merchant_controller_url' => $controllerUrl,
                'pay_environment' => __($payEnvironment)
            ]
        );
        return $this->_toHtml();
    }
}
