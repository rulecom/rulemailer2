<?php

namespace Rule\RuleMailer\Model\Api;

use Rule\ApiWrapper\ApiFactory;
use Rule\RuleMailer\Model\FieldsBuilder;

/**
 * Class Subscriber implements base operations for 'subscriber'
 */
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
     * @var\Rule\ApiWrapper\Api
     */
    private $subscriberApi;

    /**
     * @var FieldsBuilder
     */
    private $fieldsBuilder;

    /**
     * @var \Rule\RuleMailer\Helper\Data
     */
    private $helper;

    /**
     * Subscriber constructor.
     *
     * @param $apiKey
     * @param null $storeManager
     * @param \Rule\RuleMailer\Helper\Data $helper
     * @throws \Rule\ApiWrapper\Api\Exception\InvalidResourceException
     */
    public function __construct(\Rule\RuleMailer\Helper\Data $helper)
    {
        $apiKey = $helper->getApiKey();
        $this->helper = $helper;
        $this->subscriberApi = ApiFactory::make($apiKey, 'subscriber');
        $this->fieldsBuilder = new FieldsBuilder();
    }

    /**
     * @param $data
     * @return array
     */
    public function makeFields($data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $item = ['key' => $key, 'value' => $value];
            if (is_array($value) || is_object($value)) {
                $item['value'] = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $item['type'] = 'json';
            }

            $result[] = $item;
        }
        return $result;
    }

    /**
     * Add subscriber to Rulemailer.
     *
     * @param string $email   User e-mail address.
     * @param array  $tags    Tags.
     * @param array  $fields  Fields.
     * @param array  $options Options.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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

    /**
     * @param $email
     */
    public function removeSubscriber($email)
    {
        $this->subscriberApi->deleteTag($email, self::NEWSLETTER_TAG);
    }

    // product name, quantity, price?, total price?

    /**
     * @param $customer
     * @param $cart
     */
    public function updateCustomerCart($customer, $cart)
    {
        try {
            $this->subscriberApi->deleteTag($customer->getEmail(), self::CART_IN_PROGRESS_TAG);
        } catch (\Exception $e) {
            null;
        }

        $quote = $cart->getQuote();
        $subscriber = [
            'email'               => $customer->getEmail(),
            'tags'                => [self::CART_IN_PROGRESS_TAG],
            'update_on_duplicate' => true,
            'automation'          => 'reset'
        ];

         $data = $this->helper->extractValues([
             'cart' => $quote,
             'cart.products' => $this->helper->getQuoteProducts($quote),
             'cart.product_categories' => $this->helper->getProductCategories($quote),
             'customer' => $customer
             ], $this->helper->getMetaFields());
         $fields = $this->makeFields($data);
        $subscriber['fields'] = $fields;

        // $customerFields = $this->fieldsBuilder->buildCustomerFields($customer);
        // $cartFields = $this->fieldsBuilder->buildCartFields($quote);
        // $subscriber['fields'] = array_merge($customerFields, $cartFields);

        $this->subscriberApi->create($subscriber);
    }

    /**
     * @param $customer
     * @param $order
     * @param $quote
     */
    public function completeOrder($customer, $order, $quote)
    {
        try {
            $this->subscriberApi->deleteTag($customer->getEmail(), self::CART_IN_PROGRESS_TAG);
        } catch (\Exception $e) {
            null;
        }

        $subscriber = [
            'email'               => $customer->getEmail(),
            'tags'                => [self::CHECKOUT_COMPLETE_TAG],
            'update_on_duplicate' => true,
            'automation'          => 'reset'
        ];

        $data = $this->helper->extractValues([
            'order' => $order,
            'order.store' => $order->getStore(),
            'order.cart' => $quote,
            'order.cart.products' => $this->helper->getQuoteProducts($quote),
            'order.cart.product_categories' => $this->helper->getProductCategories($quote),
            'address' => $order->getShippingAddress()?$order->getShippingAddress():$order->getBillingAddress(),
            'customer' => $customer
        ], $this->helper->getMetaFields());
        $fields = $this->makeFields($data);
        $subscriber['fields'] = $fields;

        // $customerFields = $this->fieldsBuilder->buildCustomerFields($customer);
        // $orderFields = $this->fieldsBuilder->buildOrderFields($order, $quote);
        // $subscriber['fields'] = array_merge($customerFields, $orderFields);

        $this->subscriberApi->create($subscriber);
    }
}
