<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Block\Customer;

use Magento\PaymentServicesPaypal\Model\Ui\ConfigProvider;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;

/**
 * Class to render vaulted cards on customer profile
 *
 * @api
 */
class CardRenderer extends AbstractCardRenderer
{
    /**
     * Can render specified token
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    public function canRender(PaymentTokenInterface $token): bool
    {
        return $token->getPaymentMethodCode() === ConfigProvider::CC_CODE;
    }

    /**
     * Get last 4 digits of card
     *
     * @return string
     */
    public function getNumberLast4Digits(): string
    {
        return $this->getTokenDetails()['maskedCC'] ?? '';
    }

    /**
     * Get expiry of card
     *
     * @return string
     */
    public function getExpDate(): string
    {
        return $this->getTokenDetails()['expirationDate'] ?? '';
    }

    /**
     * Get url to render card issuer icon
     *
     * @return string
     */
    public function getIconUrl(): string
    {
        return $this->getIconForType($this->getCardBrand())['url'];
    }

    /**
     * Get card issuer icon height
     *
     * @return int
     */
    public function getIconHeight(): int
    {
        return $this->getIconForType($this->getCardBrand())['height'];
    }

    /**
     * Get card issuer icon width
     *
     * @return int
     */
    public function getIconWidth(): int
    {
        return $this->getIconForType($this->getCardBrand())['width'];
    }

    /**
     * Get the card type
     *
     * @return string
     */
    public function getCardBrand(): string
    {
        $cardDetails = $this->getTokenDetails();
        if (isset($cardDetails['brand'])) {
            return $this->mapCardBrand($cardDetails['brand']);
        }

        return '';
    }

    /**
     * Map the credit card brand (e.g., VISA) received from PayPal to the Commerce standard
     *
     * @param string $paypalCardBrand
     * @return string
     */
    public function mapCardBrand(string $paypalCardBrand): string
    {
        $brandMapping = [
            'AMEX' => 'AE',
            'DINERS' => 'DN',
            'DISCOVER' => 'DI',
            'ELO' => 'ELO',
            'JCB' => 'JCB',
            'MASTER_CARD' => 'MC',
            'MASTERCARD' => 'MC',
            'MAESTRO' => 'MI',
            'HIPER' => 'HC',
            'VISA' => 'VI'
        ];
        if (isset($brandMapping[$paypalCardBrand])) {
            return $brandMapping[$paypalCardBrand];
        }

        return '';
    }

    /**
     * Get card description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->getTokenDetails()['description'] ?? '...';
    }
}
