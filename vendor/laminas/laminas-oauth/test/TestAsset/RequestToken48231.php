<?php

namespace LaminasTest\OAuth\TestAsset;

use Laminas\OAuth\Http\RequestToken;
use Laminas\OAuth\Token\Request;

class RequestToken48231 extends RequestToken
{
    public function __construct()
    {
    }

    /**
     * @return Request
     */
    public function execute(?array $params = null)
    {
        return new Request();
    }

    public function setParams(array $customServiceParameters)
    {
    }
}
