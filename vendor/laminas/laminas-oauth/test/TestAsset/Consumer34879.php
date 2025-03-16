<?php

namespace LaminasTest\OAuth\TestAsset;

use Laminas\OAuth\Consumer;

class Consumer34879 extends Consumer
{
    public function getUserAuthorizationUrl(): string
    {
        return 'http://www.example.com/authorize';
    }

    public function getCallbackUrl(): string
    {
        return 'http://www.example.com/local';
    }

    public function getLastRequestToken(): Token34879
    {
        return new Token34879();
    }
}
