<?php
namespace Rule\RuleMailer\Model\Api;

use Magento\Customer\Model\Customer;
use Rule\ApiWrapper\ApiFactory;
use Rule\RuleMailer\Model\FieldsBuilder;

class Subscriber
{
    const NEWSLETTER_TAG = 'Newsletter';
    const CART_IN_PROGRESS_TAG = 'CartInProgress';
    const CHECKOUT_COMPLETE_TAG = 'Order';

    private $subscriberApi;

    public function __construct($apiKey)
    {
        $this->subscriberApi = ApiFactory::make($apiKey, 'subscriber');
        $this->fieldsBuilder = new FieldsBuilder();
    }

    public function addSubscriber($email, $tags = [], $fields = [], $options = [])
    {
        $defaultOptions = ['update_on_duplicate' => true];

        $subscriber = [
            'email' => $email,
            'tags' => $tags,
            'fields' => $fields
        ];

        try {
            $result = $this->subscriberApi->create($subscriber);
        } catch (Exception $e) {
            
        }
    }

    public function removeSubscriber($email)
    {
        try {
            $result = $this->subscriberApi->deleteTag($email, self::NEWSLETTER_TAG);
        } catch (Exception $e) {
            
        }
    }

    // product name, quantity, price?, total price?
    public function updateCustomerCart($customer, $cart)
    {
        error_log("here");
        try {
            $this->subscriberApi->deleteTag($customer->getEmail(), self::CHECKOUT_COMPLETE_TAG);
        } catch (Exception $e) {
            // log error, suppose there could be 404 if customer had logged in only during the checkout
        }

        $quote = $cart->getQuote();
        $subscriber = ['email' => $customer->getEmail(),
                       'tags' => [self::CART_IN_PROGRESS_TAG],
                       'update_on_duplicate' => true,
                       'force_automation' => true];

        $customerFields = $this->fieldsBuilder->buildCustomerFields($customer);
        $cartFields = $this->fieldsBuilder->buildCartFields($quote);
        $subscriber['fields'] = array_merge($customerFields, $cartFields);

        try {
            $result = $this->subscriberApi->create($subscriber);
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
    }

    public function completeOrder($customer, $order, $quote)
    {
        try {
            $this->subscriberApi->deleteTag($customer->getEmail(), self::CART_IN_PROGRESS_TAG);
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        $subscriber = [
            'email' => $customer->getEmail(),
            'tags' => [self::CHECKOUT_COMPLETE_TAG],
            'update_on_duplicate' => true,
            'force_automation' => true
        ];

        $customerFields = $this->fieldsBuilder->buildCustomerFields($customer);
        $orderFields = $this->fieldsBuilder->buildOrderFields($order, $quote);
        $subscriber['fields'] = array_merge($customerFields, $orderFields);

        try {
            $result = $this->subscriberApi->create($subscriber);
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
    }
}