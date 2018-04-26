<?php namespace Rule\RuleMailer\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Rule\RuleMailer\Model\Api\Subscriber;
use \Magento\Store\Model\StoreManagerInterface;

class SubscriptionObserver implements ObserverInterface
{
    /**
     * @var Subscriber
     */
    private $subscriberApi;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SubscriptionObserver constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface      $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->config = $scopeConfig;

        $apiKey = $this->config->getValue('rule_rulemailer/general/api_key', ScopeInterface::SCOPE_STORE);
        $this->subscriberApi = new Subscriber($apiKey, $storeManager);
    }

    /**
     * Execute the observer.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        try {
            $fields = [
                Subscriber::NEWSLETTER_TAG,
            ];

            $this->subscriberApi->addSubscriber($event->getSubscriber()->getEmail(), $fields);
        } catch (\Exception $e) {
            $this->logger->info("Failer to send subscriber: " . $e->getMessage());
        }
    }
}