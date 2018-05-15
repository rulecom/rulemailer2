<?php

namespace Rule\RuleMailer\Model\Api;

use Magento\Customer\Model\Customer;
use Rule\ApiWrapper\ApiFactory;
use Rule\RuleMailer\Model\FieldsBuilder;

class Subscriber
{
    /**
     * @var
     */
    const NEWSLETTER_TAG = 'Newsletter';

    /**
     * @var
     */
    const CART_IN_PROGRESS_TAG = 'CartInProgress';

    /**
     * @var
     */
    const CHECKOUT_COMPLETE_TAG = 'Order';

    /**
     * @var
     */
    private $subscriberApi;

    /**
     * @var FieldsBuilder
     */
    private $fieldsBuilder;

    /**
     * Subscriber constructor.
     *
     * @param $apiKey
     */
    public function __construct($apiKey, $storeManager=null)
    {
        $this->subscriberApi = ApiFactory::make($apiKey, 'subscriber');
        $this->fieldsBuilder = new FieldsBuilder($storeManager);
    }

    /**
     * Add subscriber to Rulemailer.
     *
     * @param string $email   User e-mail address.
     * @param array  $tags    Tags.
     * @param array  $fields  Fields.
     * @param array  $options Options.
     */
    public function addSubscriber($email, $tags = [], $fields = [], $options = [])
    {
        // Setup the data
        $subscriber = [
            'email'  => $email,
            'tags'   => $tags,
            'fields' => $fields
        ];

        // Execute the API request
        $this->subscriberApi->create($subscriber);
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
            'email'               => $customer->getEmail(),
            'tags'                => [self::CART_IN_PROGRESS_TAG],
            'update_on_duplicate' => true,
            'automation'          => 'reset'
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
            'email'               => $customer->getEmail(),
            'tags'                => [self::CHECKOUT_COMPLETE_TAG],
            'update_on_duplicate' => true,
            'automation'          => 'reset'
        ];

        $customerFields = $this->fieldsBuilder->buildCustomerFields($customer);
        $orderFields = $this->fieldsBuilder->buildOrderFields($order, $quote);
        $subscriber['fields'] = array_merge($customerFields, $orderFields);

        $result = $this->subscriberApi->create($subscriber);
    }
}