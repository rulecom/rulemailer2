<?php

namespace Rule\RuleMailer\Model\Api;

use Magento\Framework\Serialize\Serializer\Json;
use Rule\ApiWrapper\ApiFactory;
use Rule\ApiWrapper\Api\Api;
use Rule\RuleMailer\Helper\CustomerData;
use Rule\RuleMailer\Helper\OrderData;
use Rule\RuleMailer\Model\FieldsBuilder;
use Rule\RuleMailer\Helper\Data as Helper;
use Psr\Log\LoggerInterface;

/**
 * Class Subscriber implements base operations for 'subscriber'
 */
class Subscriber
{
    /**
     * @var string Tag name for 'Newsletter'
     */
    const NEWSLETTER_TAG = 'Newsletter';

    /**
     * @var string Tag name for 'CartInProgress'
     */
    const CART_IN_PROGRESS_TAG = 'CartInProgress';

    /**
     * @var string Tag name for 'OrderCompleted'
     */
    const CHECKOUT_COMPLETE_TAG = 'OrderCompleted';

    /**
     * @var string Tag name for 'OrderShipped'
     */
    const SHIPPING_COMPLETE_TAG = 'OrderShipped';

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Api
     */
    private $subscriberApi;

    /**
     * @var FieldsBuilder
     */
    private $fieldsBuilder;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var OrderData
     */
    private $orderData;

    /**
     * @var CustomerData
     */
    private $customerData;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Subscriber constructor.
     *
     * @param Json            $json
     * @param Helper          $helper
     * @param FieldsBuilder   $fieldsBuilder
     * @param LoggerInterface $logger
     * @param CustomerData    $customerData
     * @param OrderData       $orderData
     *
     * @throws \Rule\ApiWrapper\Api\Exception\InvalidResourceException
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(
        Json $json,
        Helper $helper,
        FieldsBuilder $fieldsBuilder,
        LoggerInterface $logger,
        CustomerData $customerData,
        OrderData $orderData
    ) {
        $this->json = $json;
        $this->helper = $helper;
        $this->fieldsBuilder = $fieldsBuilder;
        $this->logger = $logger;
        $this->customerData = $customerData;
        $this->orderData = $orderData;
        $this->subscriberApi = ApiFactory::make(
            $helper->getApiKey(),
            'subscriber'
        );
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
                function ($val, $key) {
                    return !is_int($key) || is_array($val) || is_object($val);
                },
                ARRAY_FILTER_USE_BOTH
            )
        );
    }

    /**
     * @param $data
     * @return array
     * @SuppressWarnings(PHPMD.ElseExpression)
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
                if (is_array($value) && $this->isMultiple($value)) {
                    $item['value'] = $value;
                    $item['type'] = 'multiple';
                } else {
                    $item['value'] = $this->json->serialize($value);
                    $item['type'] = 'json';
                }
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
            $this->logger->error($e);
        }

        $quote = $cart->getQuote();
        if (!$quote->hasItems()) {
            return;
        }
        $subscriber = [
            'email'               => $customer->getEmail(),
            'tags'                => [self::CART_IN_PROGRESS_TAG],
            'update_on_duplicate' => true,
            'automation'          => 'reset'
        ];

         $data = $this->helper->extractValues(
             [
                 'cart' => $quote,
                 'cart.products' => $this->helper->getQuoteProducts($quote),
                 'cart.product_categories' => $this->helper->getProductCategories($quote),
                 'customer' => $customer
             ],
             $this->helper->getMetaFields()
         );

         $fields = $this->makeFields($data);
         $subscriber['fields'] = $fields;

//        $customerFields = $this->fieldsBuilder->buildCustomerFields($customer);
//        $cartFields = $this->fieldsBuilder->buildCartFields($quote);
//        $subscriber['fields'] = array_merge($customerFields, $cartFields);

        $response = $this->subscriberApi->create($subscriber);
        try {
            $phone = $this->customerData->getPhoneNumberFromQuote($customer, $quote);

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
            'order.cart.products' => $this->getOrderProducts($quote, $order),
            'order.cart.product_categories' => $this->getOrderProductsCategories($quote, $order),
            'order.cart.product_names' => $this->getOrderProductNames($quote, $order),
            'address' => $order->getShippingAddress()?$order->getShippingAddress():$order->getBillingAddress(),
            'customer' => $customer
        ], $this->helper->getMetaFields());
        $this->checkFields($data, $order);
        $fields = $this->makeFields($data);
        $subscriber['fields'] = $fields;

//        $customerFields = $this->fieldsBuilder->buildCustomerFields($customer);
//        $orderFields = $this->fieldsBuilder->buildOrderFields($order, $quote);
//        $subscriber['fields'] = array_merge($customerFields, $orderFields);

        $response = $this->subscriberApi->create($subscriber);
        try {
            $phone = $this->customerData->getPhoneNumberFromQuote($customer, $quote);

            $this->subscriberApi->update($response['subscriber']['id'], ['phone_number' => $phone]);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    private function checkFields(&$data, $order) {
        if (!array_key_exists('Order.Products', $data)) {
            $data['Order.Products'] = $this->orderData->getOrderProducts($order);
        }
        if (!array_key_exists('Order.Products', $data)) {
            $data['Order.Categories'] = $this->orderData->getOrderProductCategories($order);
        }
        if (!array_key_exists('Order.Products', $data)) {
            $data['Order.Names'] = $this->orderData->getOrderProductNames($order);
        }
    }

    private function getOrderProducts($quote, $order): array {
        return count($quote->getAllVisibleItems()) ?
            $this->helper->getQuoteProducts($quote) :
            $this->orderData->getOrderProducts($order);
    }

    private function getOrderProductsCategories($quote, $order): array {
        return count($quote->getAllVisibleItems()) ?
            $this->helper->getQuoteProductCategories($quote) :
            $this->orderData->getOrderProductCategories($order);
    }

    private function getOrderProductNames($quote, $order): array {
        return count($quote->getAllVisibleItems()) ?
            $this->helper->getQuoteProductNames($quote) :
            $this->orderData->getOrderProductNames($order);
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
            'shipment.products' => $this->getShipmentProducts($shipment, $order),
            'shipment.product_categories' => $this->getShipmentProductsCategories($shipment, $order),
            'address' => $order->getShippingAddress()?$order->getShippingAddress():$order->getBillingAddress(),
            'customer' => $customer
        ], $this->helper->getMetaFields());
        $this->checkFields($data, $order);
        $fields = $this->makeFields($data);
        $subscriber['fields'] = $fields;

        $response = $this->subscriberApi->create($subscriber);

        try {
            $phone = $this->customerData->getPhoneNumberFromShipment($customer, $shipment);

            $this->subscriberApi->update($response['subscriber']['id'], ['phone_number' => $phone]);

        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    private function getShipmentProducts($shipment, $order): array {
        return count($shipment->getAllItems()) ?
            $this->helper->getShippingProducts($shipment) :
            $this->orderData->getOrderProducts($order);
    }

    private function getShipmentProductsCategories($shipment, $order): array {
        return count($shipment->getAllItems()) ?
            $this->helper->getShippingProductCategories($shipment) :
            $this->orderData->getOrderProductCategories($order);
    }
}
