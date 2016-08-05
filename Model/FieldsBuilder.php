<?php
namespace Rule\RuleMailer\Model;

class FieldsBuilder
{
    const SUBSCRIBER_GROUP = "User";
    const CART_GROUP = "Cart";
    const ORDER_GROUP = "Order";

    public function buildCartFields($quote)
    {
        $fields = [];
        $fields[] = ['key' => self::CART_GROUP . ".TotalPrice", 'value' => $quote->getSubtotal()];
        $fields[] = ['key' => self::CART_GROUP . ".Currency", 'value' => $quote->getQuoteCurrencyCode()];
        $fields[] = ['key' => self::CART_GROUP . ".Products", 'value' => $this->getProductsJson($quote), 'type' => 'json'];

        return $fields;
    }

    public function buildOrderFields($order, $quote)
    {
        if ($order->getShippingAddress()) {
            $address = $order->getShippingAddress();
        } else {
            $address = $order->getBillingAddress();
        }

        $fields = [
            ['key' => self::ORDER_GROUP . ".Status", 'value' => $order->getStatus()],
            ['key' => self::ORDER_GROUP . ".Country", 'value' => $address->getCountryId()],
            ['key' => self::ORDER_GROUP . ".City", 'value' => $address->getCity()],
            ['key' => self::ORDER_GROUP . ".Street", 'value' => implode(',', $address->getStreet())],
            ['key' => self::ORDER_GROUP . ".Region", 'value' => $address->getRegion()],
            ['key' => self::ORDER_GROUP . ".Status", 'value' => $order->getStatus()],
            ['key' => self::ORDER_GROUP . ".Currency", 'value' => $quote->getQuoteCurrencyCode()],
            ['key' => self::ORDER_GROUP . ".Subtotal", 'value' => $order->getSubtotal()],
            ['key' => self::ORDER_GROUP . ".GrandTotal", 'value' => $order->getGrandtotal()],
            ['key' => self::ORDER_GROUP . ".IncrementId", 'value' => $order->getIncrementId()],
            ['key' => self::ORDER_GROUP . ".StoreId", 'value' => $order->getStoreId()],
            ['key' => self::ORDER_GROUP . ".StoreName", 'value' => $order->getStore()->getName()],
            ['key' => self::ORDER_GROUP . ".Products", 'value' => $this->getProductsJson($quote), 'type' => 'json'],
        ];

        return $fields;
    }

    public function buildCustomerFields($customer)
    {
        $fields = [
            ['key' => self::SUBSCRIBER_GROUP . ".Firstname", 'value' => $customer->getFirstname()],
            ['key' => self::SUBSCRIBER_GROUP . ".Lastname", 'value' => $customer->getLastname()]
        ];

        if ($customer->getDob()) {
            $fields[] = ['key' => self::SUBSCRIBER_GROUP . ".BirthDate", 'value' => $customer->getDob()];
        }

        if ($customer->getDefaultBillingAddress()) {
            $address = $customer->getDefaultBillingAddress();
            $fields = $fields + [
                ['key' => self::SUBSCRIBER_GROUP . ".Country", 'value' => $address->getCountryId()],
                ['key' => self::SUBSCRIBER_GROUP . ".City", 'value' => $address->getCity()],
                ['key' => self::SUBSCRIBER_GROUP . ".Street", 'value' => implode(',', $address->getStreet())],
                ['key' => self::SUBSCRIBER_GROUP . ".Region", 'value' => $address->getRegion()],
            ];
        }
        return $fields;
    }

    protected function getProductsJson($quote)
    {
        $products = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();

            $products[] = [
                 'name' => $product->getName(),
                 'url' => $product->getProductUrl(),
                 'quantity' => $item->getQty(),
                 'price' => $item->getPrice(),
                 'description' => $item->getDescription()
           ];
        }

        return json_encode($products);
    }
}