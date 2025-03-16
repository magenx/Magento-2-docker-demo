<?php

namespace LaminasTest\OAuth\TestAsset;

use Laminas\OAuth\Consumer;

class Consumer39745 extends Consumer
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

    public function getAccessTokenUrl(): string
    {
        return 'http://www.example.com/access';
    }

    public function getLastRequestToken(): RequestToken39745
    {
        return new RequestToken39745();
    }
}
