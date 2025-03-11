<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServiceProxy\Controller\Adminhtml\Service;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpDeleteActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPatchActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\HttpPutActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\ServiceProxy\Controller\Adminhtml\AbstractProxyController;
use Magento\Backend\Model\UrlInterface;
use Psr\Log\LoggerInterface;

/**
 * Service
 */
class Proxy extends AbstractProxyController implements
    HttpGetActionInterface,
    HttpPostActionInterface,
    HttpPutActionInterface,
    HttpPatchActionInterface,
    HttpDeleteActionInterface
{
    const ADMIN_RESOURCE = 'Magento_ServiceProxy::services';

    const ACTION_PATH = 'services/service/proxy/';

    /**
     * @var UrlInterface
     */
    private $backendUrl;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $servicesList;

    /**
     * @var array
     */
    private $servicesClients;

    /**
     * @var array
     */
    private $acceptedHeaderTypes;

    /**
     * @param Context $context
     * @param UrlInterface $backenUrl
     * @param LoggerInterface $logger
     * @param array $servicesList
     * @param array $servicesClients
     * @param array $acceptedHeaderTypes
     */
    public function __construct(
        Context $context,
        UrlInterface $backenUrl,
        LoggerInterface $logger,
        array $servicesList = [],
        array $servicesClients = [],
        array $acceptedHeaderTypes = []
    ) {
        parent::__construct($context);
        $this->backendUrl = $backenUrl;
        $this->logger = $logger;
        $this->servicesList = $servicesList;
        $this->servicesClients = $servicesClients;
        $this->acceptedHeaderTypes = $acceptedHeaderTypes;
    }

    /**
     * Proxy the request to the specified service
     *
     * @return ResponseInterface
     */
    public function execute() : ResponseInterface
    {
        $method = $this->getRequest()->getMethod();

        try {
            $serviceName = $this->getServiceName();
            if ($serviceName === '' || !in_array($serviceName, $this->servicesList)) {
                $this->logger->error(
                    sprintf('Service with name %s does not register in the service proxy controller.', $serviceName)
                );
                return $this->generateErrorResponse(404, 'Service not found');
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->generateErrorResponse(500, 'Internal Server Error');
        }
        $headers = $this->getAcceptedHeaders($this->getRequest());

        return $this->servicesClients[$serviceName]->request(
            $this->getServiceRoute(),
            $method,
            $headers,
            $this->getRequest()->getContent() ?: ''
        );
    }

    /**
     * Set an error response with HTTP status code and error message
     *
     * @param int $code
     * @param string $message
     * @return ResponseInterface
     */
    private function generateErrorResponse(int $code, string $message) : ResponseInterface
    {
        return $this->getResponse()->setHttpResponseCode($code)->setBody($message);
    }

    /**
     * Add accepted headers to the request
     *
     * @param RequestInterface $request
     * @return array
     */
    private function getAcceptedHeaders(RequestInterface $request) :array
    {
        $headers = $request->getHeaders();
        $addedHeaders = [];

        foreach ($headers as $header) {
            if (in_array(get_class($header), $this->acceptedHeaderTypes)) {
                $addedHeaders[$header->getFieldName()] = $header->getFieldValue();
            }
        }

        return $addedHeaders;
    }

    /**
     * Get the params of the requst
     *
     * @return string
     */
    private function getQueryParams() : string
    {
        $url = $this->backendUrl->getCurrentUrl();
        $requestString = str_contains($url, '?') ? explode('?', $url)[1] : '';
        if (empty($requestString)) {
            return '';
        }
        if (str_contains($requestString, 'isAjax=true')) {
            parse_str($requestString, $params); //phpcs:ignore
            unset($params['isAjax']);
            return http_build_query($params);
        }
        return $requestString;
    }

    /**
     * Extract the service path from the request URL string
     *
     * @return string
     */
    private function getServicePath() : string
    {
        $queryParams = $this->getQueryParams();
        $actionUrl = str_replace($this->backendUrl->getRouteUrl(), '', $this->backendUrl->getUrl(self::ACTION_PATH));
        $servicePath = explode($actionUrl, $this->backendUrl->getCurrentUrl())[1];
        $servicePath = str_contains($servicePath, '?') ? explode('?', $servicePath)[0] : $servicePath;
        return $queryParams ? $servicePath . '?' . $queryParams : $servicePath;
    }

    /**
     * Get requested service name
     *
     * @return string
     */
    private function getServiceName() : string
    {
        return explode('/', $this->getServicePath())[0];
    }

    /**
     * Get requested service route
     *
     * @return string
     */
    private function getServiceRoute() : string
    {
        $servicePath = $this->getServicePath();
        $serviceName = $this->getServiceName();
        $serviceNameLength = strlen($serviceName);

        // Remove the service name and the following slash from the path
        return substr($servicePath, $serviceNameLength + 1);
    }
}
