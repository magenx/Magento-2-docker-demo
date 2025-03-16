<?php

namespace Laminas\OAuth\Config;

use Laminas\OAuth\Token\TokenInterface;

interface ConfigInterface
{
    public function setOptions(array $options);

    /**
     * @param string $key
     */
    public function setConsumerKey($key);

    public function getConsumerKey();

    /**
     * @param string $secret
     */
    public function setConsumerSecret($secret);

    public function getConsumerSecret();

    /**
     * @param string $method
     */
    public function setSignatureMethod($method);

    public function getSignatureMethod();

    /**
     * @param string $scheme
     */
    public function setRequestScheme($scheme);

    public function getRequestScheme();

    /**
     * @param string $version
     */
    public function setVersion($version);

    public function getVersion();

    /**
     * @param string $url
     */
    public function setCallbackUrl($url);

    public function getCallbackUrl();

    /**
     * @param string $url
     */
    public function setRequestTokenUrl($url);

    public function getRequestTokenUrl();

    /**
     * @param string $method
     */
    public function setRequestMethod($method);

    public function getRequestMethod();

    /**
     * @param string $url
     */
    public function setAccessTokenUrl($url);

    public function getAccessTokenUrl();

    /**
     * @param string $url
     */
    public function setUserAuthorizationUrl($url);

    public function getUserAuthorizationUrl();

    public function setToken(TokenInterface $token);

    public function getToken();
}
