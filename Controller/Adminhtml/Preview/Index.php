<?php
namespace Rule\RuleMailer\Controller\Adminhtml\Preview;

use Magento\Store\Model\StoreRepository;

/**
 * Class Index implements controller for admin-panel preview action
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Index extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    /**
     * @var \Rule\RuleMailer\Model\FieldsBuilder
     */
    private $fieldsBuilder;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $jsonResultFactory;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;
    /**
     * @var \Magento\Customer\Model\CustomerRepository
     */
    private $customerRepository;
    /**
     * @var \Rule\RuleMailer\Helper\Data
     */
    private $helper;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var \Magento\Sales\Api\OrderAddressRepositoryInterface
     */
    private $orderAddressRepository;
    /**
     * @var StoreRepository
     */
    private $storeRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     * @param \Rule\RuleMailer\Model\FieldsBuilder $fieldsBuilder
     * @param \Rule\RuleMailer\Helper\Data $helper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param StoreRepository $storeRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Rule\RuleMailer\Model\FieldsBuilder $fieldsBuilder,
        \Rule\RuleMailer\Helper\Data $helper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Store\Model\StoreRepository $storeRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->fieldsBuilder = $fieldsBuilder;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->storeRepository = $storeRepository;
        $this->quoteRepository = $quoteRepository;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;

        parent::__construct($context);
    }

    /**
     * New subscription action
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\Controller\Result\Json
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $subject = $this->getRequest()->getParam('subject');
        $fields = json_decode($this->getRequest()->getParam('fields'), true);

        /** @var \Magento\Framework\DataObject $object */
        $object = [];
        $error = null;
        switch ($subject) {
            case 'quote':
                $quote = null;
                try {
                    if (!is_numeric($id)) {
                        $customer = $this->customerRepository->get($id);
                        $quote = $this->quoteRepository->getForCustomer($customer->getId());
                    } else {
                        $quote = $this->quoteRepository->get($id);
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $error = "Quote '$id' or quote for customer '$id' were not found";
                }

                if ($quote) {
                    $object['cart'] = $quote;
                    $object['cart.products'] = $this->helper->getQuoteProducts($quote);
                    $object['cart.product_categories'] = $this->helper->getProductCategories($quote);
                    $object['customer'] = $quote->getCustomer();
                }
                break;
            case 'order':
                $order = null;
                try {
                    $order = $this->orderRepository->get($id);
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $id, 'eq')->create();
                    $list = $this->orderRepository->getList($searchCriteria)->getItems();
                    if (count($list) >0) {
                        $order = $list[0];
                    }
                }

                if ($order) {
                    $quote = $this->quoteRepository->get($order->getQuoteId());
                    $object['order  '] = $order;
                    $object['order.cart'] = $quote;
                    $object['order.cart.products'] = $this->helper->getQuoteProducts($quote);
                    $object['order.cart.product_categories'] = $this->helper->getProductCategories($quote);
                    $object['order.store'] = $this->storeRepository->getById($order->getStoreId());
                    $object['address'] = $this->orderAddressRepository->get(
                        $order->getShippingAddressId() ? $order->getShippingAddressId() : $order->getBillingAddressId()
                    );
                    if ($order->getCustomerId()) {
                        $object['customer'] = $this->customerRepository->getById($order->getCustomerId());
                    } elseif ($quote->getCustomer()) {
                        $object['customer'] = $quote->getCustomer();
                    }
                } else {
                    $error = "Order #$id not found";
                }

                break;
            case 'shipment':
                $shipment = null;
                try {
                    $shipment = $this->shipmentRepository->get($id);
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $id, 'eq')->create();
                    $list = $this->orderRepository->getList($searchCriteria)->getItems();
                    if (count($list) >0) {
                        $shipment = $list[0];
                    }
                }

                if ($shipment) {
                    $object['order'] = $shipment->getOrder();
                    $object['shipment'] = $shipment;
                    $object['shipment.products'] =  $this->helper->getShippingProducts($shipment);
                    $object['shipment.product_categories'] = $this->helper->getShippingProductCategories($shipment);
                    $object['order.store'] = $this->storeRepository->getById($shipment->getStoreId());
                    $object['address'] = $this->orderAddressRepository->get(
                        $shipment->getShippingAddressId() ? $shipment->getShippingAddressId() :
                        $shipment->getBillingAddressId()
                    );
                    if ($shipment->getCustomerId()) {
                        $object['customer'] = $this->customerRepository->getById($shipment->getCustomerId());
                    }
                } else {
                    $error = "Shipment #$id not found";
                }

                break;
            case 'customer':
                try {
                    $object['customer'] = $this->customerRepository->getById($id);
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    try {
                        $object['customer'] = $this->customerRepository->get($id);
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $error = "Customer '$id' not found";
                    }
                }
                break;
        }

        $result = $this->jsonResultFactory->create();
        if ($error) {
            $result->setData($error);
        } else {
            $values = $this->helper->extractValues($object, empty($fields) ? [] : $fields);
            $result->setData($values);
        }
        return $result;
    }
}
