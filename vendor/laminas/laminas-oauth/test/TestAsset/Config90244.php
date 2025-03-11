<?php

namespace LaminasTest\OAuth\TestAsset;

use Laminas\OAuth\Config\StandardConfig;
use Laminas\OAuth\Token\Access;

class Config90244 extends StandardConfig
{
    public function getConsumerKey(): string
    {
        return '1234567890';
    }

    public function getSignatureMethod(): string
    {
        return 'HMAC-SHA1';
    }

    public function getVersion(): string
    {
        return '1.0';
    }

    public function getRequestTokenUrl(): string
    {
        return 'http://www.example.com/request';
    }

    public function getToken(): Access
    {
        $token = new Access();
        $token->setToken('abcde');
        return $token;
    }

    public function getRequestMethod(): string
    {
        return 'POST';
    }
}
