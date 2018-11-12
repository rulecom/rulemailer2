<?php

namespace Rule\RuleMailer\Plugin\Magento\Customer\Model;

use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Rule\RuleMailer\Model\Api\Subscriber;

/**
 * Class AccountManagement
 *
 * @package  Rule\RuleMailer\Plugin\Magento\Customer\Model
 * @author   Robert Lord, Codepeak AB <robert@codepeak.se>
 * @link     https://codepeak.se
 */
class AccountManagement
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Subscriber
     */
    protected $subscriberApi;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * AccountManagement constructor.
     */
    public function __construct(ScopeConfigInterface $scopeConfig, Cart $cart, CustomerFactory $customerFactory)
    {
        $this->scopeConfig = $scopeConfig;
        $this->cart = $cart;
        $this->customerFactory = $customerFactory;
    }

    /**
     * This is started around isEmailAvailable call. We will catch the e-mail used for the field in checkout
     * to populate and data in RuleMailer, if we're supposed to.
     *
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param \Closure                                  $proceed
     * @param                                           $customerEmail
     * @param                                           $websiteId
     *
     * @return mixed
     */
    public function aroundIsEmailAvailable(
        \Magento\Customer\Model\AccountManagement $subject,
        \Closure $proceed,
        $customerEmail,
        $websiteId = null
    ) {
        // Perform the original request
        $originalResult = $proceed($customerEmail, $websiteId);

        // Let's make it safe to avoid any errors
        try {
            // Fetch the API key
            $apiKey = $this->scopeConfig->getValue('rule_rulemailer/general/api_key', ScopeInterface::SCOPE_STORE);

            // Fetch setting for aggressive abandoned cart
            $aggressive = $this->scopeConfig->getValue(
                'rule_rulemailer/general/aggressive_abandoned_cart',
                ScopeInterface::SCOPE_STORE
            );

            // Fetch the subscriber API
            $this->subscriberApi = new Subscriber($apiKey);

            // Default is that we don't send to Rule
            $sendToRule = false;

            // If the original result is false, then e-mail exists
            if (!$originalResult) {
                $sendToRule = true;
            } else {
                // E-mail doesn't exist, if aggressive mode is enabled, use the e-mail address
                if ($aggressive == '1') {
                    $sendToRule = true;
                }
            }

            // Check if we should send the customer to rule
            if ($sendToRule) {
                // Create a temporary customer account
                $customer = $this->customerFactory->create();

                // Populate the model with the e-mail address
                $customer->setEmail($customerEmail);

                // Send the request
                $this->subscriberApi->updateCustomerCart($customer, $this->cart);
            }
        } catch (\Exception $e) {
            // Do nothing, silently continue with normal operations
        }

        // Return the original result
        return $originalResult;
    }
}