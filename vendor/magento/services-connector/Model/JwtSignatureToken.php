<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ServicesConnector\Model;

use \Firebase\JWT\JWT;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\ServicesConnector\Api\JwtTokenInterface;
use Magento\ServicesConnector\Exception\PrivateKeySignException;
use Psr\Log\LoggerInterface;

class JwtSignatureToken implements JwtTokenInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function getSignature($privateKey) {
        try {
            $payload = array(
                'exp' => time()+300,
                'iss' => '8E6307BF5D775FA00A495EF9@AdobeOrg',
                'sub' => '3181519E5E2714990A495E2E@techacct.adobe.com',
                'https://ims-na1.adobelogin.com/s/ent_reactor_sdk' => true,
                'https://ims-na1.adobelogin.com/s/asset_compute_meta' => true,
                'https://ims-na1.adobelogin.com/s/ent_adobeio_sdk' => true,
                'https://ims-na1.adobelogin.com/s/event_receiver_api' => true,
                'aud' => 'https://ims-na1.adobelogin.com/c/9ec9d05f79eb443e8f76ce477b1e413c'
            );

            return JWT::encode($payload, $privateKey, 'RS256');
        } catch (\Exception $ex) {
            $this->logger->error(__('Private key signing failed. Check that the private key is correct.'));
            throw new PrivateKeySignException(__('Private key signing failed'));
        }
    }
}
