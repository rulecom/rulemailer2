<?php
namespace Rule\RuleMailer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\DataObject as Object;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

use Rule\RuleMailer\Model\Api\Subscriber;

class SubscriptionObserver implements ObserverInterface
{
    private $subscriberApi;

    private $config;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->config = $scopeConfig;

        $apiKey = $this->config->getValue('rule_rulemailer/general/api_key', ScopeInterface::SCOPE_STORE);
        $this->subscriberApi = new Subscriber($apiKey);
    }

    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $this->subscriberApi->addSubscriber($event->getSubscriber()->getEmail(), [Subscriber::NEWSLETTER_TAG]);
    }
}