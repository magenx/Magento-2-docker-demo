<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Block\Cart;

use Magento\Framework\Message\MessageInterface;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Message\Factory as MessageFactory;
use Magento\Framework\Message\CollectionFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Checkout\Helper\Cart;

/**
 * @api
 */
class ValidationMessages extends \Magento\Framework\View\Element\Messages
{
    /**
     * @var Cart
     */
    private $cartHelper;

    /**
     * @param Context $context
     * @param MessageFactory $messageFactory
     * @param CollectionFactory $collectionFactory
     * @param ManagerInterface $messageManager
     * @param InterpretationStrategyInterface $interpretationStrategy
     * @param Cart $cartHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        MessageFactory $messageFactory,
        CollectionFactory $collectionFactory,
        ManagerInterface $messageManager,
        InterpretationStrategyInterface $interpretationStrategy,
        Cart $cartHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $messageFactory,
            $collectionFactory,
            $messageManager,
            $interpretationStrategy,
            $data
        );
        $this->cartHelper = $cartHelper;
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        if ($this->cartHelper->getItemsCount()) {
            $this->addQuoteMessages();
            $this->addMessages($this->messageManager->getMessages(true));
        }
        return parent::_prepareLayout();
    }

    /**
     * Add quote messages
     */
    private function addQuoteMessages() : void
    {
        $messages = [];
        /** @var MessageInterface $message */
        foreach ($this->cartHelper->getQuote()->getMessages() as $message) {
            if (!$message->getIdentifier()) {
                try {
                    $messages[] = $this->messageManager
                        ->createMessage($message->getType())
                        ->setText($message->getText());
                } catch (\InvalidArgumentException $e) {
                    $messages[] = $e->getMessage();
                }
            } else {
                $messages[] = $message;
            }
        }
        $this->messageManager->addUniqueMessages(
            $messages
        );
    }
}
