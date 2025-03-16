<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQlServer\Controller\Adminhtml\Graphql;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\GraphQl\Exception\ExceptionFormatter;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\GraphQlServer\Model\Server;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;

/**
 * Graphql front controller
 */
class Gateway extends Action implements HttpGetActionInterface, HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var SerializerInterface
     */
    private $jsonSerializer;

    /**
     * @var ExceptionFormatter
     */
    private $graphQlError;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Server
     */
    private $server;

    /**
     * @param SerializerInterface $jsonSerializer
     * @param ExceptionFormatter $graphQlError
     * @param JsonFactory $jsonFactory
     * @param Server $server
     * @param Context $context
     */
    public function __construct(
        SerializerInterface $jsonSerializer,
        ExceptionFormatter $graphQlError,
        JsonFactory $jsonFactory,
        Server $server,
        Context $context
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->graphQlError = $graphQlError;
        $this->jsonFactory = $jsonFactory;
        $this->server = $server;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $statusCode = 200;
        $jsonResult = $this->jsonFactory->create();
        $data = $this->getDataFromRequest($this->getRequest());
        $result = [];
        try {
            $query = $data['query'] ?? '';
            $variables = $data['variables'] ?? null;
            $result = $this->server->execute($query, $variables);
        } catch (\Throwable $error) {
            $result['errors'] = isset($result['errors']) ? $result['errors'] : [];
            $result['errors'][] = $this->graphQlError->create($error);
            $statusCode = ExceptionFormatter::HTTP_GRAPH_QL_SCHEMA_ERROR_STATUS;
        }
        $jsonResult->setHttpResponseCode($statusCode);
        return $jsonResult->setData($result);
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function _processUrlKeys()
    {
        if (!$this->_auth->isLoggedIn()) {
            $this->getResponse()->representJson($this->formatError('Authentication failed'));
            return false;
        }
        if ($this->_backendUrl->useSecretKey() && !$this->_validateSecretKey()) {
            $this->getResponse()->representJson($this->formatError('URL Key validation failed'));
            return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_GraphQlServer::admin_graphql');
    }

    /**
     * Get data from request body or query string
     *
     * @param RequestInterface $request
     * @return array
     */
    private function getDataFromRequest(RequestInterface $request): array
    {
        /** @var Http $request */
        if ($request->isPost()) {
            $data = $this->jsonSerializer->unserialize($request->getContent());
        } elseif ($request->isGet()) {
            $data = $request->getParams();
            $data['variables'] = isset($data['variables']) ?
                $this->jsonSerializer->unserialize($data['variables']) : null;
            $data['variables'] = is_array($data['variables']) ?
                $data['variables'] : null;
        } else {
            return [];
        }
        return $data;
    }

    /**
     * Format GraphQL error
     *
     * @param string $message
     * @return string
     */
    public function formatError(string $message): string
    {
        return json_encode([
            'errors' => [
                [
                    'message' => $message
                ]
            ]
        ]);
    }
}
