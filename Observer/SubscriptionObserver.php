<?php namespace Rule\RuleMailer\Observer;

use Psr\Log\LoggerInterface;
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

    private $logger;

    public function __construct(ScopeConfigInterface $scopeConfig, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->config = $scopeConfig;

        $apiKey = $this->config->getValue('rule_rulemailer/general/api_key', ScopeInterface::SCOPE_STORE);
        $this->subscriberApi = new Subscriber($apiKey);
    }

    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        try {
            $this->subscriberApi->addSubscriber($event->getSubscriber()->getEmail(), [Subscriber::NEWSLETTER_TAG]);
        } catch (\Exception $e) {
            $this->logger->info("Failer to send subscriber: " . $e->getMessage());
        }
    }
}