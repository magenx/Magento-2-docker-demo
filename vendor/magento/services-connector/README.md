# Local installation from source code

* Change directory to Magento root
* Add the following lines to `composer.json`
```json
"minimum-stability": "dev",
"repositories": [
    {
        "type": "git",
        "url":  "git@git.corp.adobe.com:magento-saas/magento-services-connector.git"
    }
]
```
* Launch `composer require magento/services-connector:dev-master` or add `"magento/services-connector": "dev-master"` dependency to require section of composer.json

* Add a following settings to `app/etc/env.php` for internal setup
```php
    'system' => [
        'default' => [
            'services_connector' => [
                'sandbox_gateway_url' => 'https://qa-api.magedevteam.com/',
                'production_gateway_url' => 'https://int-api.magedevteam.com/',
                'api_portal_url' => 'https://account-stage.magedevteam.com/apiportal/index/index/',
                'ims_technical_account_credentials' => [
                    'ims_url' => 'https://ims-na1-stg1.adobelogin.com/',
                ]
            ]
        ]
    ]
```
* Enable ServicesConnector module using `./bin/magento module:enable Magento_ServicesConnector` CLI command
* Launch `./bin/magento app:config:import` CLI command in order to apply config
* Launch `./bin/magento setup:upgrade` CLI command in order to upgrade Magento instance


# Installation
* Launch `composer require magento/services-connector:1.*` or add `"magento/services-connector": "1.*"` dependency to require section of composer.json
* Enable ServicesConnector module using `./bin/magento module:enable Magento_ServicesConnector` CLI command
* Launch `./bin/magento setup:upgrade` CLI command in order to upgrade Magento instance


# Usage

```php
class YourClass {
    //Inject clientResolver in your class
    public function __construct(\Magento\ServicesConnector\Api\ClientResolverInterface $clientResolver)
    {
        $this->clientResolver = $clientResolver;
    }
    
    public function sendSomeRequest()
    { 
        $client = $this->clientResolver->createHttpClient('your_extension', 'production|sandbox');
        // Will send GET request to https://api.gateway.domain/yourservice/path
        // Also GET request will contain API key of your extension
        $client->request('GET', '/yourservice/path');    
    }
}

class YourClass {
    //Inject KeyValidationInterface in your class
    public function __construct(\Magento\ServicesConnector\Api\KeyValidationInterface $keyValidation)
    {
        $this->keyValidation = $keyValidation;
    }
    
    public function keyValidationAwareMethod()
    {
        try {
            if ($this->keyValidation->execute('your_extension', 'production|sandbox')) {
                //the key is valid, you can send requests
            } else {
                //API keys is not valid            
            }
        } catch (\Magento\ServicesConnector\Exception\KeyNotFoundException $e) {
            //The key is empty
            //You can redirect to API Portal URL
        }
    
    }
}
```
