<?php namespace Rule\RuleMailer\Observer;

use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\DataObject as Object;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Session;
use Rule\RuleMailer\Model\Api\Subscriber;
use Magento\Customer\Model\CustomerFactory;

class CheckoutObserver implements ObserverInterface
{
    private $subscriberApi;

    private $config;

    private $customerFactory;

    private $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session $customerSession,
        CustomerFactory $customerFactory,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->config = $scopeConfig;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;

        $apiKey = $this->config->getValue('rule_rulemailer/general/api_key', ScopeInterface::SCOPE_STORE);
        $this->subscriberApi = new Subscriber($apiKey);
    }

    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();

        try {
            if ($this->customerSession->isLoggedIn()) {
                $customer = $this->customerSession->getCustomer();
            } else {
                $customer = $this->customerFactory->create();

                $customer->setEmail($event->getOrder()->getCustomerEmail());
                $customer->setFirstname($event->getOrder()->getBillingAddress()->getFirstname());
                $customer->setLastname($event->getOrder()->getBillingAddress()->getLastname());
            }

            $this->subscriberApi->completeOrder($customer, $event->getOrder(), $event->getQuote());
        } catch (\Exception $e) {
            $this->logger->info("Filed to complete order: " . $e->getMessage());
        }
    }
}