<?php namespace Rule\RuleMailer\Observer;

use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session;
use Rule\RuleMailer\Model\Api\Subscriber;

class AddProductObserver implements ObserverInterface
{
    private $subscriberApi;

    private $config;

    private $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session $customerSession,
        LoggerInterface $logger,
        Subscriber $subscriber
    ) {
        $this->config = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->logger = $logger;

        $this->subscriberApi = $subscriber->getApi();
    }

    public function execute(Observer $observer)
    {
        try {
            $event = $observer->getEvent();
            $cart = $event->getCart();

            if ($this->customerSession->isLoggedIn()) {
                $this->subscriberApi->updateCustomerCart($this->customerSession->getCustomer(), $cart);
            }
        } catch (\Exception $e) {
            $this->logger->info("Failed to update cart:" . $e->getMessage());
        }
    }
}
