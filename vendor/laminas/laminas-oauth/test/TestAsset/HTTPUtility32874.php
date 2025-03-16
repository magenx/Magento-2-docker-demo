<?php

namespace LaminasTest\OAuth\TestAsset;

use Laminas\OAuth\Http\Utility;

use function md5;

class HTTPUtility32874 extends Utility
{
    public function __construct()
    {
    }

    public function generateNonce(): string
    {
        return md5('1234567890');
    }

    public function generateTimestamp(): string
    {
        return '12345678901';
    }

    /**
     * @param string $signatureMethod
     * @param string $consumerSecret
     * @param string $accessTokenSecret
     * @param string $method
     * @param string $url
     * @return string
     */
    public function sign(
        array $params,
        $signatureMethod,
        $consumerSecret,
        $accessTokenSecret = null,
        $method = null,
        $url = null
    ) {
        return md5('0987654321');
    }
}
