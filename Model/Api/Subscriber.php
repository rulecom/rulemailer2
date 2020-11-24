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
    const CHECKOUT_COMPLETE_TAG = 'OrderCompleted';

    /**
     * @var
     */
    const SHIPPING_COMPLETE_TAG = 'OrderShipped';

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
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Subscriber constructor.
     *
     * @param \Rule\RuleMailer\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     * @throws \Rule\ApiWrapper\Api\Exception\InvalidResourceException
     */
    public function __construct(\Rule\RuleMailer\Helper\Data $helper, \Psr\Log\LoggerInterface $logger)
    {
        $apiKey = $helper->getApiKey();
        $this->helper = $helper;
        $this->subscriberApi = ApiFactory::make($apiKey, 'subscriber');
        $this->fieldsBuilder = new FieldsBuilder();
        $this->logger = $logger;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isMultiple($value)
    {
        return 0 == count(
            array_filter(
                $value,
                function ($v, $k) {
                    return !is_int($k) || is_array($v) || is_object($v);
                },
                ARRAY_FILTER_USE_BOTH
            )
        );
    }

    /**
     * @param $data
     * @return array
     */
    public function makeFields($data)
    {
        if (!array_key_exists('Subscriber.Source', $data)) {
            $data['Subscriber.Source'] = 'MagentoRule';
        }

        $result = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $item = ['key' => $key, 'value' => $value];
            if (is_array($value) || is_object($value)) {
                $item['value'] = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $item['type'] = 'json';
                if (is_array($value) && $this->isMultiple($value)) {
                    $item['type'] = 'multiple';
                }
            };

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
            $this->logger->error($e);
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

        $response = $this->subscriberApi->create($subscriber);
        try {
            $phone = !empty($customer->getTelephone())?$customer->getTelephone():
                ($customer->getDefaultBillingAddress()?$customer->getDefaultBillingAddress()->getTelephone():
                    ($customer->getDefaultShippingAddress()?$customer->getDefaultShippingAddress()->getTelephone():
                        ($quote->getBillingAddress()?$quote->getBillingAddress()->getTelephone():
                            ($quote->getShippingAddress()?$quote->getShippingAddress()->getTelephone(): null))));

            $this->subscriberApi->update($response['subscriber']['id'], ['phone_number' => $phone]);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
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
            $this->logger->error($e);
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

        $response = $this->subscriberApi->create($subscriber);
        try {
            $phone = !empty($customer->getTelephone())?$customer->getTelephone():
                ($customer->getDefaultBillingAddress()?$customer->getDefaultBillingAddress()->getTelephone():
                    ($customer->getDefaultShippingAddress()?$customer->getDefaultShippingAddress()->getTelephone():
                        ($quote->getBillingAddress()?$quote->getBillingAddress()->getTelephone():
                            ($quote->getShippingAddress()?$quote->getShippingAddress()->getTelephone(): null))));

            $this->subscriberApi->update($response['subscriber']['id'], ['phone_number' => $phone]);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     */
    public function completeShipping($customer, $order, $shipment)
    {
        $subscriber = [
            'email'               => $customer->getEmail(),
            'tags'                => [self::SHIPPING_COMPLETE_TAG],
            'update_on_duplicate' => true,
            'automation'          => 'reset'
        ];

        $data = $this->helper->extractValues([
            'order' => $order,
            'order.store' => $order->getStore(),
            'shipment' => $shipment,
            'shipment.products' => $this->helper->getShippingProducts($shipment),
            'shipment.product_categories' => $this->helper->getShippingProductCategories($shipment),
            'address' => $order->getShippingAddress()?$order->getShippingAddress():$order->getBillingAddress(),
            'customer' => $customer
        ], $this->helper->getMetaFields());
        $fields = $this->makeFields($data);
        $subscriber['fields'] = $fields;

        $response = $this->subscriberApi->create($subscriber);
        try {
            $phone = !empty($customer->getTelephone())?$customer->getTelephone():
                ($customer->getDefaultBillingAddress()?$customer->getDefaultBillingAddress()->getTelephone():
                    ($customer->getDefaultShippingAddress()?$customer->getDefaultShippingAddress()->getTelephone():
                        ($shipment->getBillingAddress()?$shipment->getBillingAddress()->getTelephone():
                            ($shipment->getShippingAddress()?$shipment->getShippingAddress()->getTelephone(): null))));

            $this->subscriberApi->update($response['subscriber']['id'], ['phone_number' => $phone]);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }
}
