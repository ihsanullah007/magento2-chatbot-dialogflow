<?php
namespace IU\Chatbot\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_CHATBOT = 'chatbot/general/';

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CHATBOT . $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function isEnabled($storeId = null)
    {
        return $this->getConfigValue('enabled', $storeId);
    }

    public function getProjectId($storeId = null)
    {
        return $this->getConfigValue('project_id', $storeId);
    }

    public function getServiceAccountKey($storeId = null)
    {
        return $this->getConfigValue('service_account_key', $storeId);
    }

    public function getWelcomeMessage($storeId = null)
    {
        return $this->getConfigValue('welcome_message', $storeId);
    }

    public function getPosition($storeId = null)
    {
        return $this->getConfigValue('position', $storeId);
    }
}