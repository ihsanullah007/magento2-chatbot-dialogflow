<?php
namespace IU\Chatbot\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;

class Dialogflow
{
    protected $orderRepository;
    protected $productRepository;
    protected $helper;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ProductRepositoryInterface $productRepository,
        \IU\Chatbot\Helper\Data $helper
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->helper = $helper;
    }

    public function processText($text)
    {  
        try {
            $keyPath = $this->helper->getServiceAccountKey();
            if (strpos($keyPath, '{') === 0) {
                $tmpFile = tempnam(sys_get_temp_dir(), 'dialogflow_key');
                file_put_contents($tmpFile, $keyPath);
                $keyPath = $tmpFile;
            }

            if (empty($this->helper->getProjectId())) {
                return ['fulfillmentText' => 'Dialogflow project ID not configured'];
            }

            $sessionsClient = new SessionsClient([
                'credentials' => $keyPath 
            ]);
            
            $session = $sessionsClient->sessionName($this->helper->getProjectId(), uniqid());
 
            $textInput = new TextInput();
            $textInput->setText($text);
            $textInput->setLanguageCode("en-US");
            
            $queryInput = new QueryInput();
            $queryInput->setText($textInput);

            $response = $sessionsClient->detectIntent($session, $queryInput);
            $queryResult = $response->getQueryResult();
            
            $sessionsClient->close();

            if ($queryResult->getAllRequiredParamsPresent()) {
                $intent = $queryResult->getIntent()->getDisplayName();
                $parameters = json_decode($queryResult->getParameters()->serializeToJsonString(), true);
                
                return $this->processIntent($intent, $parameters, $queryResult);
            }
             
            return ['fulfillmentText' => $queryResult->getFulfillmentText()];
            
        } catch (\Exception $e) {
            return [
                'fulfillmentText' => "Sorry, I'm having trouble understanding that."
            ];
        }
    }

    public function processIntent($intent, $parameters, $queryResult)
    {
        switch ($intent) {
            case 'Order Status':
                return $this->handleOrderStatus($parameters['order_id']);
            
            case 'Product Info':
                return $this->handleProductInfo($parameters['product_sku']);
            
            case 'Store Hours':
                return $this->handleStoreHours();
            
            case 'Greeting_Salam':
            case 'Default Welcome Intent':
            default:
                return ['fulfillmentText' => $queryResult->getFulfillmentText()];
        }
    }

    protected function handleOrderStatus($orderId)
    {
        try {
            $order = $this->orderRepository->get($orderId);
            return [
                'fulfillmentText' => "Order #{$orderId} is currently {$order->getStatusLabel()}."
            ];
        } catch (\Exception $e) {
            return ['fulfillmentText' => "Sorry, I couldn't find order #{$orderId}."];
        }
    }

    protected function handleProductInfo($sku)
    {
        try {
            $product = $this->productRepository->get($sku);
            return [
                'fulfillmentText' => "{$product->getName()} is currently " . 
                    ($product->getIsInStock() ? 'in stock' : 'out of stock') . 
                    " and priced at {$product->getFormattedPrice()}."
            ];
        } catch (\Exception $e) {
            return ['fulfillmentText' => "Sorry, I couldn't find product with SKU {$sku}."];
        }
    }

    protected function handleStoreHours()
    {
        return [
            'fulfillmentText' => "Our store is open from 9 AM to 9 PM, Monday to Saturday. " .
                "We're closed on Sundays."
        ];
    }
}