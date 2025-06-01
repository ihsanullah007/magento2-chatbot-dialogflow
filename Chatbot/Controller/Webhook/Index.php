<?php
namespace IU\Chatbot\Controller\Webhook;

use IU\Chatbot\Model\Dialogflow;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Index extends Action implements CsrfAwareActionInterface
{
    protected $resultJsonFactory;
    protected $dialogflow;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Dialogflow $dialogflow
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->dialogflow = $dialogflow;
        parent::__construct($context);
    }

    // CSRF BYPASS METHODS
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        if (!$this->getRequest()->isPost()) {
            return $result->setData(['fulfillmentText' => 'Method not allowed']);
        }
        
        try {
            $input = json_decode($this->getRequest()->getContent(), true); 
            
            // Check if this is a Dialogflow webhook request
            if (isset($input['queryResult'])) {
                // This is a Dialogflow webhook call with parsed intent
                $queryResult = $input['queryResult'];
                $intent = $queryResult['intent']['displayName'] ?? null;
                $parameters = $queryResult['parameters'] ?? [];
                
                // Process with our intent handler
                $response = $this->dialogflow->processIntent($intent, $parameters, $queryResult);
            } else {
                // This is a direct text input from our frontend
                $userText = $input['text'] ?? '';
                $response = $this->dialogflow->processText($userText); 
            }
            
        } catch (\Exception $e) {
            $response = ['fulfillmentText' => 'Sorry, I encountered an error.'];
        }
        
        return $result->setData($response);
    }
}