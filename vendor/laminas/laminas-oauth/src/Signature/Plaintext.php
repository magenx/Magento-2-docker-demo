<?php

namespace Laminas\OAuth\Signature;

use function implode;

class Plaintext extends AbstractSignature
{
    /**
     * Sign a request
     *
     * @param  array $params
     * @param  null|string $method
     * @param  null|string $url
     * @return string
     */
    public function sign(array $params, $method = null, $url = null)
    {
        if ($this->tokenSecret === null) {
            return $this->consumerSecret . '&';
        }
        return implode('&', [$this->consumerSecret, $this->tokenSecret]);
    }
}
