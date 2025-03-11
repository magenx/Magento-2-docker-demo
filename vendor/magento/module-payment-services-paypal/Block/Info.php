<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\PaymentServicesPaypal\Block;

use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Payment\Gateway\ConfigInterface;

/**
 * @api
 */
class Info extends ConfigurableInfo
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param Context $context
     * @param ConfigInterface $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        array $data = []
    ) {
        parent::__construct($context, $config, $data);
        $this->config = $config;
    }
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * @inheritdoc
     */
    protected function getValueView($field, $value)
    {
        $fieldMap = $this->getData('valueMapper')[$field] ?? null;
        if ($fieldMap && $value) {
            return $fieldMap[$value];
        }
        return parent::getValueView($field, $value);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $payment = $this->getInfo();
        $storedFields = explode(',', (string)$this->config->getValue('paymentInfoKeys'));

        foreach ($storedFields as $field) {
            $value = strtr($field, $payment->getAdditionalInformation());
            if ($value !== $field) {
                $payment->setAdditionalInformation($field, $value);
            }
        }

        return parent::_prepareSpecificInformation($transport);
    }
}
