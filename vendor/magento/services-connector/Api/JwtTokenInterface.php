<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ServicesConnector\Api;

use Magento\ServicesConnector\Exception\PrivateKeySignException;

/**
 * Generates a JWT Token based on the private key provided
 */
interface JwtTokenInterface
{
    /**
     * Generates JWT token given the private key
     * @param string $privateKey
     * @return string JWT signature token encrypted with the private key
     * @throws PrivateKeySignException
     */
    public function getSignature($privateKey);
}
