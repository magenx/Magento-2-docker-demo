<?php

namespace LaminasTest\OAuth\TestAsset;

use Laminas\Http\Client;

class HTTPClient32874 extends Client
{
    /**
     * @return mixed|string
     */
    public function getRawData()
    {
        return $this->getRequest()->getContent();
    }
}
