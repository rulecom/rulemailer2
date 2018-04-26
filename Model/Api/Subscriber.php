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
    private $fieldsBuilder;

    public function __construct($apiKey)
    {
        $this->subscriberApi = ApiFactory::make($apiKey, 'subscriber');
        $this->fieldsBuilder = new FieldsBuilder();
    }

    public function addSubscriber($email, $tags = [], $fields = [], $options = [])
    {
        $subscriber = [
            'email' => $email,
            'tags' => $tags,
            'fields' => $fields
        ];

        $result = $this->subscriberApi->create($subscriber);
    }

    public function removeSubscriber($email)
    {
        $result = $this->subscriberApi->deleteTag($email, self::NEWSLETTER_TAG);
    }

    // product name, quantity, price?, total price?
    public function updateCustomerCart($customer, $cart)
    {
        try {
            $this->subscriberApi->deleteTag($customer->getEmail(), self::CART_IN_PROGRESS_TAG);
        } catch (\Exception $e) {
            // Do nothing for now
        }

        $quote = $cart->getQuote();
        $subscriber = [
            'email' => $customer->getEmail(),
            'tags' => [self::CART_IN_PROGRESS_TAG],
            'update_on_duplicate' => true,
            'automation' => 'reset'
        ];

        $customerFields = $this->fieldsBuilder->buildCustomerFields($customer);
        $cartFields = $this->fieldsBuilder->buildCartFields($quote);
        $subscriber['fields'] = array_merge($customerFields, $cartFields);

        $result = $this->subscriberApi->create($subscriber);
    }

    public function completeOrder($customer, $order, $quote)
    {
        try {
            $this->subscriberApi->deleteTag($customer->getEmail(), self::CART_IN_PROGRESS_TAG);
        } catch (\Exception $e) {

        }

        $subscriber = [
            'email' => $customer->getEmail(),
            'tags' => [self::CHECKOUT_COMPLETE_TAG],
            'update_on_duplicate' => true,
            'automation' => 'reset'
        ];

        $customerFields = $this->fieldsBuilder->buildCustomerFields($customer);
        $orderFields = $this->fieldsBuilder->buildOrderFields($order, $quote);
        $subscriber['fields'] = array_merge($customerFields, $orderFields);

        $result = $this->subscriberApi->create($subscriber);
    }
}