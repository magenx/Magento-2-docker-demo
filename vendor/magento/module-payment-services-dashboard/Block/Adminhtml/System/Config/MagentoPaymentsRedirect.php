<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesDashboard\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Model\Config;
use Magento\Framework\View\Helper\Js;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * @api
 */
class MagentoPaymentsRedirect extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var Config
     */
    private $backendConfig;

    /**
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param Config $backendConfig
     * @param SecureHtmlRenderer $secureRenderer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        Config $backendConfig,
        SecureHtmlRenderer $secureRenderer,
        array $data = []
    ) {
        $this->backendConfig = $backendConfig;
        $this->secureRenderer = $secureRenderer;
        parent::__construct($context, $authSession, $jsHelper, $data, $secureRenderer);
    }

    /**
     * @inheritdoc
     */
    public function render(AbstractElement $element)
    {
        $legacyEnabled = (bool) $this->backendConfig
            ->getConfigDataValue('payment/payment_services/legacy_admin_enabled');

        if (!$legacyEnabled) {
            return parent::render($element);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function _getFrontendClass($element)
    {
        // this is used to render the html header with a button redirect to the remote react settings
        return parent::_getFrontendClass($element)
            . ' with-button';
    }

    /**
     * @inheritDoc
     */
    protected function _getHeaderTitleHtml($element)
    {
        $html = '<div class="config-heading">';

        $htmlId = $element->getHtmlId();
        $html .= '<div class="button-container"><button type="button"'
            . ' class="button action-configure'
            . '" id="'
            . $htmlId
            . '-head" >'
            . '<span>'
            . __('Settings')
            . '</span></button>';

        $html .= $this->secureRenderer->renderEventListenerAsTag(
            'onclick',
            "magentoPaymentsToggleSolution.call(this, '"
            . $htmlId
            . "'); event.preventDefault();",
            'button#' . $htmlId . '-head'
        );

        $html .= '</div><div class="heading"><strong>'
            . $element->getLegend()
            . '</strong>';

        if ($element->getComment()) {
            $html .= '<span class="heading-intro">'
                . $element->getComment()
                . '</span>';
        }
        $html .= '<div class="config-alt"></div></div></div>';

        return $html;
    }

    /**
     * @inheritDoc
     */
    protected function _getHeaderCommentHtml($element)
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    protected function _getExtraJs($element)
    {
        $script = "require(['jquery', 'prototype'], function(jQuery){
            window.magentoPaymentsToggleSolution = function (id) {
               window.location.assign('" . $this->getUrl('paymentservicesdashboard/dashboard/index')
                . "#/settings" . "');
            }
        });";

        return $this->_jsHelper->getScript($script);
    }
}
