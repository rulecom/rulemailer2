<?php

namespace Rule\RuleMailer\Model;

use Magento\Framework\UrlInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Rule\RuleMailer\Helper\Data as Helper;

class FieldsBuilder
{
    /**
     * @var string Data prefix for SUBSCRIBER_GROUP
     */
    const SUBSCRIBER_GROUP = 'User';

    /**
     * @var string Data prefix for CART_GROUP
     */
    const CART_GROUP = 'Cart';

    /**
     * @var string Data prefix for ORDER_GROUP
     */
    const ORDER_GROUP = 'Order';

    /**
     * @var string Data prefix for ADDRESS_GROUP
     */
    const ADDRESS_GROUP = 'Address';

    /**
     * @var StoreManagerInterface
     */
    private $storeManagerInterface;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var Json
     */
    private $json;

    /**
     * FieldsBuilder constructor.
     *
     * @param StoreManagerInterface  $storeManagerInterface
     * @param Helper $helper
     */
    public function __construct(
        StoreManagerInterface $storeManagerInterface,
        Helper $helper,
        Json $json
    ) {
        $this->storeManagerInterface = $storeManagerInterface;
        $this->helper = $helper;
        $this->json = $json;
    }

    /**
     * @param Quote $quote
     * @return array
     */
    public function buildCartFields(Quote $quote)
    {
        return [
            ['key' => self::CART_GROUP . '.TotalPrice', 'value' => $quote->getSubtotal()],
            ['key' => self::CART_GROUP . '.Currency', 'value' => $quote->getQuoteCurrencyCode()],
            ['key' => self::CART_GROUP . '.Products', 'value' => $this->getProductsJson($quote), 'type' => 'json']
        ];
    }

    /**
     * @param OrderInterface $order
     * @param Quote $quote
     * @return array
     */
    public function buildOrderFields(OrderInterface $order, Quote $quote)
    {
        if ($order->getShippingAddress()) {
            $address = $order->getShippingAddress();
        } else {
            $address = $order->getBillingAddress();
        }

        return [
            ['key' => self::ORDER_GROUP . '.Status', 'value' => $order->getStatus()],
            ['key' => self::ORDER_GROUP . '.Country', 'value' => $address->getCountryId()],
            ['key' => self::ORDER_GROUP . '.City', 'value' => $address->getCity()],
            ['key' => self::ORDER_GROUP . '.Street', 'value' => implode(',', $address->getStreet())],
            ['key' => self::ORDER_GROUP . '.Region', 'value' => $address->getRegion() ? $address->getRegion() : ''],
            ['key' => self::ORDER_GROUP . '.Postcode', 'value' => $address->getPostcode()],
            ['key' => self::ORDER_GROUP . '.Currency', 'value' => $quote->getQuoteCurrencyCode()],
            ['key' => self::ORDER_GROUP . '.Subtotal', 'value' => $order->getSubtotal()],
            ['key' => self::ORDER_GROUP . '.GrandTotal', 'value' => $order->getGrandtotal()],
            ['key' => self::ORDER_GROUP . '.IncrementId', 'value' => $order->getIncrementId()],
            ['key' => self::ORDER_GROUP . '.StoreId', 'value' => $order->getStoreId()],
            ['key' => self::ORDER_GROUP . '.StoreName', 'value' => $order->getStore()->getName()],
            ['key' => self::ORDER_GROUP . '.Products', 'value' => $this->getProductsJson($quote), 'type' => 'json'],
            [
                'key'   => self::ORDER_GROUP . '.Categories',
                'value' => $this->helper->getProductCategories($quote),
                'type'  => 'multiple'
            ],
            [
                'key'   => self::ORDER_GROUP . '.Names',
                'value' => $this->helper->getProductNames($quote),
                'type'  => 'multiple'
            ]
        ];
    }

    /**
     * @param $customer
     * @return array
     */
    public function buildCustomerFields($customer)
    {
        $fields = [
            ['key' => self::SUBSCRIBER_GROUP . '.Source', 'value' => 'MagentoRule']
        ];

        if ($customer->getFirstname()) {
            $fields[] = ['key' => self::SUBSCRIBER_GROUP . '.Firstname', 'value' => $customer->getFirstname()];
        }

        if ($customer->getLastname()) {
            $fields[] = ['key' => self::SUBSCRIBER_GROUP . '.Lastname', 'value' => $customer->getLastname()];
        }

        if ($customer->getDob()) {
            $fields[] = ['key' => self::SUBSCRIBER_GROUP . '.BirthDate', 'value' => $customer->getDob()];
        }

        if ($customer->getGender()) {
            $fields[] = ['key' => self::SUBSCRIBER_GROUP . '.Gender', 'value' => $customer->getGender()];
        }

        return $fields;
    }

    /**
     * @param Quote $quote
     * @return false|string
     */
    private function getProductsJson(Quote $quote)
    {
        $products = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();

            $products[] = [
                'name'     => $product->getName(),
                'url'      => $product->getProductUrl(),
                'quantity' => $item->getQty(),
                'price'    => $item->getPrice(),
                'image'    => $quote->getStore()
                        ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
                    . 'catalog/product' . $product->getImage()
            ];
        }

        return $this->json->serialize($products);
    }
}
