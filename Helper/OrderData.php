<?php

namespace Rule\RuleMailer\Helper;

class OrderData
{
    /**
     * Get Order Product Names.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getOrderProductNames(Order $order)
    {
        $names = [];

        foreach ($order->getAllVisibleItems() as $item) {
            /** @var \Magento\Order\Model\Order\Item $item */

            $name = $item->getName();

            if ($name && !in_array($name, $names)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Get Order Products.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getOrderProducts(\Magento\Sales\Model\Order $order)
    {
        $products = [];
        foreach ($order->getItems() as $item) {
            if ($item->getParentItemId()) {
                continue;
            }

            $product = $item->getProduct();

            $products[] = [
                'name'     => $item->getName(),
                'url'      => $product->getProductUrl(),
                'quantity' => $item->getQtyOrdered(),
                'price'    => $item->getPrice(),
                'image'    => $order->getStore()
                        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                    . 'catalog/product' . $product->getImage()
            ];
        }

        return $products;
    }

    /**
     * Get Order Product Categories.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getOrderProductCategories(Order $order)
    {
        $categories = [];

        foreach ($order->getItems() as $item) {
            $productCategories = $item->getProduct()->getCategoryCollection()->addAttributeToSelect('name');

            foreach ($productCategories->getItems() as $categoryModel) {
                $category = $categoryModel->getName();

                if ($category != null && !in_array($category, $categories)) {
                    $categories[] = $category;
                }
            }
        }

        return $categories;
    }
}
