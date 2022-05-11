<?php namespace Rule\RuleMailer\Helper;

use Magento\Quote\Model\Quote;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Data contains shared functions used cross extension
 */
class Data extends AbstractHelper
{
    /**
     * @param null $store_id
     * @return mixed
     */
    public function getApiKey($store_id = null)
    {
        return $this->scopeConfig->getValue(
            'rule_rulemailer/general/api_key',
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
    }

    /**
     * @param null $store_id
     * @return mixed
     */
    public function getUseTransactional($store_id = null)
    {
        return $this->scopeConfig->getValue(
            'rule_rulemailer/general/use_transactional',
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
    }

    /**
     * @param null $store_id
     * @return mixed
     */
    public function getMetaFields($store_id = null)
    {
        return json_decode(
            $this->scopeConfig->getValue(
                'rule_rulemailer/general/meta_fields',
                ScopeInterface::SCOPE_STORE,
                $store_id
            ),
            true
        );
    }

    /**
     * @param $subject
     * @param string $prefix
     * @return array
     */
    public function getMethods($subject, $prefix = '')
    {
        $result = [];
        try {
            $class = new \ReflectionClass($subject);
            $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                /** @var \ReflectionMethod $method */
                $name = $method->getName();
                if (strpos($name, 'get') === 0 && $method->getNumberOfParameters() == 0) {
                    $key = strtolower(substr(preg_replace('/([A-Z])/', '_$1', $name), 4));
                    $label = substr(preg_replace('/([A-Z])/', ' $1', $name), 4);
                    foreach (['sql', 'select', 'iterator', 'resource', 'connection', 'file'] as $substring) {
                        if (strpos($key, $substring) !== false) {
                            continue 2;
                        }
                    }
                    if ($prefix != "") {
                        $key = $prefix . '.' . $key;
                        $label = ucwords(str_replace('.', ' ', $prefix)) . ' ' . $label;
                    }
                    $result[$key] = $label;
                }
            }
        } catch (\ReflectionException $e) {
            null;
        }
        return $result;
    }

    /**
     * @param $subject
     * @return bool
     */
    public function isNumericArray($subject)
    {
        if (is_array($subject)) {
            return count(
                array_filter(
                    $subject,
                    function ($k) {
                        return !is_int($k);
                    },
                    ARRAY_FILTER_USE_KEY
                )
            ) == 0;
        }
        return false;
    }

    /**
     * @param $subject
     * @return array
     */
    public function collapseArray(&$subject)
    {
        if (is_array($subject)) {
            foreach ($subject as $key => $value) {
                $path = explode('.', $key);
                $last = array_pop($path);
                $item = &$subject;
                foreach ($path as $step) {
                    if (!array_key_exists($step, $item) || !is_array($item[$step])) {
                        $item[$step] = [];
                    }
                    $item = &$item[$step];
                }
                $item[$last] = $value;
                if ($key != $last) {
                    unset($subject[$key]);
                }
            }
        }
        return $subject;
    }

    /**
     * @param $subject
     * @param array $fields
     * @param array $stack
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function extractValues($subject, $fields = [], $stack = [])
    {
        $result = [];

        if (is_object($subject) && $fields != null) {
            if (in_array($subject, $stack) || count($stack) > 100) {
                return 'cyclic reference';
            }
            $stack[] = $subject;
        }

        // checking if given object is class
        $class = null;
        try {
            if (is_object($subject)) {
                $class = new \ReflectionClass($subject);
            }
        } catch (\Exception $e) {
            null;
        }

        // if no fields path specified, taking all possible of them
        if (empty($fields)) {
            if (is_array($subject)) {
                $fields = array_keys($subject);
            } elseif ($subject instanceof \Magento\Framework\DataObject) {
                $fields = array_keys($subject->getData());
            } elseif ($class != null) {
                $fields = array_keys($this->getMethods($subject));
            } else {
                return $subject;
            }
        }

        // expanding path key
        $keys = [];
        foreach ($fields as $key => $field) {
            if (strpos($field, '{') === 0) {
                continue;
            }
            if ($field !== '') {
                $path = explode('.', $field);
                $key = array_shift($path);

                if (is_array($subject) || $subject instanceof \Magento\Framework\DataObject) {
                    for ($i=count($path); $i>0; $i--) {
                        $test = $key . '.' . implode('.', array_slice($path, 0, $i));
                        if (is_array($subject) && array_key_exists($test, $subject) ||
                            $subject instanceof \Magento\Framework\DataObject && $subject->hasData($test)
                        ) {
                            $key = $test;
                            $path = array_slice($path, $i);
                            break;
                        }
                    }
                }

                if (!array_key_exists($key, $keys)) {
                    $keys[$key] = [];
                }
                $keys[$key][] = implode('.', $path);
            } else {
                $value = $subject;
                if (is_array($value) || is_object($value)) {
                    $value = $this->extractValues($value, null, $stack);
                }
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $result[$k] = $v;
                    }
                } else {
                    $result[''] = $value;
                }
            }
        }

        // collecting key values
        foreach ($keys as $key => $values) {
            $value = null;

            if (is_array($subject) && array_key_exists($key, $subject)) {
                $value = $subject[$key];
            } elseif ($subject instanceof \Magento\Framework\DataObject && $subject->hasData($key)) {
                $value = $subject->getData($key);
            } elseif ($class != null) {
                $method = 'get' . str_replace('_', '', ucwords($key, "_"));
                if ($class->hasMethod($method) && $class->getMethod($method)->getNumberOfParameters() == 0) {
                    $value = $subject->$method();
                } else {
                    continue;
                }
            } else {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $value = $this->extractValues($value, $values, $stack);
            }

            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $result[trim($key . '.' . $k, '.')] = $v;
                }
            } else {
                $result[$key] = $value;
            }
        }

        if (!array_key_exists(0, $fields)) {
            $output = [];
            foreach ($fields as $key => $path) {
                if (strpos($path, '{') === 0 || strpos($path, '"') === 0) {
                    $output[$key] = json_decode($path);
                    if ($output[$key] === null) {
                        $output[$key] = json_decode(substr($path, 1, -1));
                    }
                    continue;
                }
                foreach ($result as $item => $value) {
                    if ($item == $path) {
                        $output[$key] = $value;
                    } elseif (strpos($item, $path)===0) {
                        if (!array_key_exists($key, $output)) {
                            $output[$key] = [];
                        }
                        $output[$key][substr($item, strlen($path)+1)] = $value;
                    }
                }

                if (array_key_exists($key, $output) && is_array($output[$key])) {
                    $this->collapseArray($output[$key]);
                }
            }
            $result = $output;
        }

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    public function getQuoteProducts(\Magento\Quote\Model\Quote $quote)
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
                        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                    . 'catalog/product' . $product->getImage()
            ];
        }

        return $products;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    public function getShippingProducts(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $products = [];
        /** @var \Magento\Sales\Model\Order\Shipment\Item $item */
        foreach ($shipment->getAllItems() as $item) {
            $product = $item->getOrderItem()->getProduct();

            $products[] = [
                'name'     => $product->getName(),
                'url'      => $product->getProductUrl(),
                'quantity' => $item->getQty(),
                'price'    => $item->getPrice(),
                'image'    => $shipment->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                    . 'catalog/product' . $product->getImage()
            ];
        }

        return $products;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    public function getShippingProductCategories(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $categories = [];
        /** @var \Magento\Sales\Model\Order\Shipment\Item $item */
        foreach ($shipment->getAllItems() as $item) {
            $productCategories = $item->getOrderItem()->getProduct()->getCategoryCollection()
                ->addAttributeToSelect('name');

            foreach ($productCategories->getItems() as $categoryModel) {
                $category = $categoryModel->getName();

                if ($category != null && !in_array($category, $categories)) {
                    $categories[] = $category;
                }
            }
        }

        return $categories;
    }

    /**
     * @param Quote $quote
     * @return array
     */
    public function getProductCategories(Quote $quote)
    {
        $categories = [];

        foreach ($quote->getAllVisibleItems() as $item) {
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

    /**
     * Get Product names.
     *
     * @param Quote $quote
     *
     * @return array
     */
    public function getProductNames(Quote $quote)
    {
        $names = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            /** @var \Magento\Quote\Model\Quote\Item $item */

            $name = $item->getName();

            if ($name && !in_array($name, $names)) {
                $names[] = $name;
            }
        }

        return $names;
    }
}
