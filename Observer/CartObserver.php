<?php namespace Rule\RuleMailer\Observer;

use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session;
use Rule\RuleMailer\Model\Api\Subscriber;

class CartObserver implements ObserverInterface
{
    private $subscriber;

    private $config;

    private $logger;

    private $customerSession;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session $customerSession,
        LoggerInterface $logger,
        Subscriber $subscriber
    ) {
        $this->subscriber = $subscriber;
        $this->config = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->logger = $logger;

    }

    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $cart = $event->getCart();

        if ($this->customerSession->isLoggedIn()) {
            try {
                $this->subscriber->updateCustomerCart($this->customerSession->getCustomer(), $cart);
            } catch (\Exception $e) {
                $this->logger->info("Failed to update cart:" . $e->getMessage());
            }
        }
    }
}
