<?php namespace Rule\RuleMailer\Observer;

use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session;
use Rule\RuleMailer\Model\Api\Subscriber;
use Magento\Customer\Model\CustomerFactory;

/**
 * Class CheckoutObserver listener for 'checkout_submit_all_after' event
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class CheckoutObserver implements ObserverInterface
{
    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CheckoutObserver constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $customerSession
     * @param CustomerFactory $customerFactory
     * @param LoggerInterface $logger
     * @param Subscriber $subscriber
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session $customerSession,
        CustomerFactory $customerFactory,
        LoggerInterface $logger,
        Subscriber $subscriber
    ) {
        $this->subscriber = $subscriber;
        $this->logger = $logger;
        $this->config = $scopeConfig;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * @param Observer $observer
     */
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

            $this->subscriber->completeOrder($customer, $event->getOrder(), $event->getQuote());
        } catch (\Exception $e) {
            $this->logger->info("Filed to complete order: " . $e->getMessage());
        }
    }
}
