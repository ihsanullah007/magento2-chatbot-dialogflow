<?php
namespace IU\Chatbot\Block;

use Magento\Framework\View\Element\Template;
use IU\Chatbot\Helper\Data;

class Chat extends Template
{
    protected $helper;

    public function __construct(
        Template\Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    public function isEnabled()
    {
        return $this->helper->isEnabled();
    }

    public function getWelcomeMessage()
    {
        return $this->helper->getWelcomeMessage();
    }

    public function getPosition()
    {
        return $this->helper->getPosition();
    }
}