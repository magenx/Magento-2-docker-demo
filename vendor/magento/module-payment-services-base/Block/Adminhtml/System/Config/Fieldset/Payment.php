<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Block\Adminhtml\System\Config\Fieldset;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\View\Helper\Js;
use Magento\Config\Model\Config;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Phrase;

/**
 * @api
 */
class Payment extends \Magento\Config\Block\System\Config\Form\Fieldset
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
     * @var ProductMetadataInterface
     */
    private $productMeta;

    /**
     * @var bool
     */
    protected $isCollapsedDefault = true;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param Config $backendConfig
     * @param SecureHtmlRenderer $secureRenderer
     * @param ProductMetadataInterface $productMeta
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        Config $backendConfig,
        SecureHtmlRenderer $secureRenderer,
        ProductMetadataInterface $productMeta,
        array $data = []
    ) {
        $this->backendConfig = $backendConfig;
        $this->secureRenderer = $secureRenderer;
        $this->productMeta = $productMeta;
        parent::__construct($context, $authSession, $jsHelper, $data, $secureRenderer);
    }

    /**
     * Render fieldset html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $legacyEnabled = (bool) $this->backendConfig
            ->getConfigDataValue('payment/payment_services/legacy_admin_enabled');

        if ($legacyEnabled) {
            return parent::render($element);
        }

        return null;
    }

    /**
     * Get frontend class
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getFrontendClass($element)
    {
        return parent::_getFrontendClass($element)
            . ' with-button'
            . ($this->_isCollapseState($element) ? ' open active' : '');
    }

    /**
     * Return header title part of html for fieldset
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {
        $html = '<div class="config-heading">';

        $htmlId = $element->getHtmlId();
        $html .= '<div class="button-container"><button type="button"'
            . ($this->_isCollapseState($element) ? '' : ' disabled="disabled"')
            . ' class="button action-configure'
            . ($this->_isCollapseState($element) ? ' open' : '')
            . '" id="'
            . $htmlId
            . '-head" >'
            . '<span class="state-closed">'
            . __('Configure')
            . '</span><span class="state-opened">'
            . __('Close')
            . '</span></button>';

        $html .= $this->secureRenderer->renderEventListenerAsTag(
            'onclick',
            "magentoPaymentsToggleSolution.call(this, '"
            . $htmlId
            . "', '"
            . $this->getUrl('adminhtml/*/state')
            . "'); event.preventDefault();",
            'button#' . $htmlId . '-head'
        );

        $html .= '</div><div class="heading"><strong>'
            . $element->getLegend()
            . '</strong>';

        if ($element->getComment()) {
            $html .= '<div class="heading-intro">'
                . $this->getExtensionDescription()
                . '</div>';
        }
        $logosUrl = 'Magento_PaymentServicesBase::images/logos/';
        $html .= '<div class="logos">
        <img src="' . $this->getViewFileUrl($logosUrl . 'visa.svg') . '" alt="Visa">
        <img src="' . $this->getViewFileUrl($logosUrl . 'mastercard.svg') . '" alt="Mastercard">
        <img src="' . $this->getViewFileUrl($logosUrl . 'amex.svg') . '" alt="American Express">
        <img src="' . $this->getViewFileUrl($logosUrl . 'discover.png') . '" alt="Discover">
        <img src="' . $this->getViewFileUrl($logosUrl . 'venmo.svg') . '" alt="Venmo">
        <img src="' . $this->getViewFileUrl($logosUrl . 'apple-pay.svg') . '" alt="Apple Pay">
        <img src="' . $this->getViewFileUrl($logosUrl . 'google-pay.svg') . '" alt="Google Pay">
        <img src="' . $this->getViewFileUrl($logosUrl . 'paypal.svg') . '" alt="PayPal">
        <img src="' . $this->getViewFileUrl($logosUrl . 'paylater.png') . '" alt="PayPal Pay Later">
</div>';
        $html .= '<div class="config-alt"></div></div></div>';

        return $html;
    }

    /**
     * Return header html for fieldset
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getHeaderHtml($element)
    {
        if ($element->getIsNested()) {
            $html = '<tr class="nested"><td colspan="4"><div class="'
                . $this->_getFrontendClass($element)
                . '">';
        } else {
            $html = '<div class="'
                . $this->_getFrontendClass($element)
                . '">';
        }

        $html .= '<div class="entry-edit-head admin__collapsible-block">'
            . '<span id="' .
            $element->getHtmlId()
            . '-link" class="entry-edit-head-link"></span>';

        $html .= $this->_getHeaderTitleHtml($element);

        $html .= '</div>';
        $html .= '<input id="'
            . $element->getHtmlId()
            . '-state" name="config_state['
            . $element->getId()
            . ']" type="hidden" value="'
            . $this->_isCollapseState($element)
            . '" />';
        $html .= '<fieldset class="'
            . $this->_getFieldsetCss()
            . '" id="' . $element->getHtmlId()
            . '"><legend>'
            . $element->getLegend()
            . '</legend>';

        $html .= $this->_getHeaderCommentHtml($element);

        $html .= '<table cellspacing="0" class="form-list">'
            . '<colgroup class="label"></colgroup>'
            . '<colgroup class="value"></colgroup>';

        if ($this->getRequest()->getParam('website') || $this->getRequest()->getParam('store')) {
            $html .= '<colgroup class="use-default"></colgroup>';
        }
        $html .= '<colgroup class="scope-label"></colgroup><colgroup class=""></colgroup><tbody>';

        return $html;
    }

    /**
     * Return header comment part of html for fieldset
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getHeaderCommentHtml($element)
    {
        return '';
    }

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

    /**
     * Return js code for fieldset:
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getExtraJs($element)
    {
        $script = "require(['jquery', 'prototype'], function(jQuery){
            window.magentoPaymentsToggleSolution = function (id, url) {
                var doScroll = false;
                Fieldset.toggleCollapse(id, url);
                if ($(this).hasClassName(\"open\")) {
                    \$$(\".with-button button.button\").forEach(function(anotherButton) {
                        if (anotherButton != this && $(anotherButton).hasClassName(\"open\")) {
                            $(anotherButton).click();
                            doScroll = true;
                        }
                    }.bind(this));
                }
                if (doScroll) {
                    var pos = Element.cumulativeOffset($(this));
                    window.scrollTo(pos[0], pos[1] - 45);
                }
            }
        });";

        return $this->_jsHelper->getScript($script);
    }

    /**
     * Prepares description for the extension based on the current installed version of Commerce
     *
     * @return string
     */
    private function getExtensionDescription(): string
    {
        $commerceEdition = $this->getCommerceEdition();

        $ph1 = new Phrase("Accept popular payment methods—credit/debit cards, PayPal, Apple Pay, Google Pay, "
            . "and Venmo—and deliver your customers a smooth checkout experience.");
        $ph2 = new Phrase("Designed for $commerceEdition merchants of all sizes, Payment Services delivers:");
        $ph3 = new Phrase("A fully-integrated payment experience");
        $ph4 = new Phrase("Competitive processing fees");
        $ph5 = new Phrase("Advanced financial reporting capabilities");
        $ph6 = new Phrase("Secure and up-to-date extension with SaaS");
        $ph7 = new Phrase(
            "If you have product questions or need help with setup, follow this link and fill out the form"
        );

        return <<<EOD
            <p>$ph1</p>
            <p>$ph2</p>
            <ul>
                <li>$ph3</li>
                <li>$ph4</li>
                <li>$ph5</li>
                <li>$ph6</li>
            </ul>
            <p><a href="https://business.adobe.com/resources/payment-services.html" target="_blank">$ph7</a></p>
            </br>
        EOD;
    }

    /**
     * Get Commerce Edition that we use for the description
     *
     * @return string
     */
    private function getCommerceEdition(): string
    {
        $defaultEdition = "Adobe Commerce and Magento Open Source";
        $editionOptions = [
            "Community" => "Magento Open Source",
            "Enterprise" => "Adobe Commerce",
            "B2B" => "Adobe Commerce"
        ];

        return $editionOptions[$this->productMeta->getEdition()] ?? $defaultEdition;
    }
}
