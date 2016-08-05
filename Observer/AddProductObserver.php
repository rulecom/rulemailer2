<?php
namespace Rule\RuleMailer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\DataObject as Object;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Session;
use Rule\RuleMailer\Model\Api\Subscriber;

class AddProductObserver implements ObserverInterface
{
    private $subscriberApi;

    private $config;

    public function __construct(ScopeConfigInterface $scopeConfig, Session $customerSession)
    {
        $this->config = $scopeConfig;

        $this->customerSession = $customerSession;

        $apiKey = $this->config->getValue('rule_rulemailer/general/api_key', ScopeInterface::SCOPE_STORE);
        $this->subscriberApi = new Subscriber($apiKey);
    }

    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $cart = $event->getCart();

        if ($this->customerSession->isLoggedIn()) {
            $this->subscriberApi->updateCustomerCart($this->customerSession->getCustomer(), $cart);
        }
    }
}