<?php

namespace LaminasTest\OAuth\TestAsset;

use Laminas\OAuth\Http\AccessToken;
use Laminas\OAuth\Token\Access;

class AccessToken48231 extends AccessToken
{
    public function __construct()
    {
    }

    /**
     * @param array|null $params
     * @return Access
     */
    public function execute(?array $params = null)
    {
        return new Access();
    }

    public function setParams(array $customServiceParameters)
    {
    }
}
