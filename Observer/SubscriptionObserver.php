<?php namespace Rule\RuleMailer\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Rule\RuleMailer\Model\Api\Subscriber;

class SubscriptionObserver implements ObserverInterface
{
    /**
     * @var
     */
    const NEWSLETTER_GROUP = 'Newsletter';

    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * SubscriptionObserver constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface      $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        Resolver $resolver,
        Subscriber $subscriber
    ) {
        $this->subscriber = $subscriber;
        $this->logger = $logger;
        $this->config = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->resolver = $resolver;

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
            // Set the tags for this subscriber
            $tags = [
                Subscriber::NEWSLETTER_TAG,
            ];

            // Fetch current store
            $store = $this->storeManager->getStore();

            // Setup our custom fields
            $fields = [
                ['key' => self::NEWSLETTER_GROUP . '.StoreId', 'value' => $store->getStoreId()],
                ['key' => self::NEWSLETTER_GROUP . '.WebsiteId', 'value' => $store->getWebsiteId()],
                ['key' => self::NEWSLETTER_GROUP . '.StoreName', 'value' => $store->getName()],
                ['key' => self::NEWSLETTER_GROUP . '.Currency', 'value' => $store->getCurrentCurrency()->getCode()],
                ['key' => self::NEWSLETTER_GROUP . '.Language', 'value' => $this->resolver->getLocale()],
            ];

            $this->subscriber->addSubscriber($event->getSubscriber()->getEmail(), $tags, $fields);
        } catch (\Exception $e) {
            $this->logger->info("Failer to send subscriber: " . $e->getMessage());
        }
    }
}