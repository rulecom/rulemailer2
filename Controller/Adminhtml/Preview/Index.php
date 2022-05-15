<?php

namespace Rule\RuleMailer\Controller\Adminhtml\Preview;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreRepository;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Rule\RuleMailer\Model\FieldsBuilder;
use Rule\RuleMailer\Helper\Data as Helper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderAddressRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Index implements controller for admin-panel preview action
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * @var FieldsBuilder
     */
    private $fieldsBuilder;

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var OrderAddressRepositoryInterface
     */
    private $orderAddressRepository;

    /**
     * @var StoreRepository
     */
    private $storeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var Json
     */
    private $json;

    /**
     * Initialize dependencies.
     *
     * @param Context                         $context
     * @param JsonFactory                     $jsonResultFactory
     * @param FieldsBuilder                   $fieldsBuilder
     * @param Helper                          $helper
     * @param OrderRepositoryInterface        $orderRepository
     * @param OrderAddressRepositoryInterface $orderAddressRepository
     * @param CartRepositoryInterface         $quoteRepository
     * @param ShipmentRepositoryInterface     $shipmentRepository
     * @param StoreRepository                 $storeRepository
     * @param CustomerRepositoryInterface     $customerRepository
     * @param SearchCriteriaBuilder           $searchCriteriaBuilder
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        FieldsBuilder $fieldsBuilder,
        Helper $helper,
        OrderRepositoryInterface $orderRepository,
        OrderAddressRepositoryInterface $orderAddressRepository,
        CartRepositoryInterface $quoteRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        StoreRepository $storeRepository,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Json $json
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
        $this->json = $json;

        parent::__construct($context);
    }

    /**
     * New subscription action
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\Controller\Result\Json
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $subject = $this->getRequest()->getParam('subject');
        $fields = $this->json->unserialize($this->getRequest()->getParam('fields'));

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
                } catch (NoSuchEntityException $e) {
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
                } catch (NoSuchEntityException $e) {
                    $searchCriteria = $this->searchCriteriaBuilder->addFilter(
                        'increment_id',
                        $id,
                        'eq'
                    )->create();

                    $list = $this->orderRepository->getList($searchCriteria)->getItems();

                    if (count($list) > 0) {
                        $order = $list[0];
                    }
                }

                if ($order) {
                    $quote = $this->quoteRepository->get($order->getQuoteId());
                    $object['order'] = $order;
                    $object['order.cart'] = $quote;
                    $object['order.cart.products'] = $this->helper->getQuoteProducts($quote);
                    $object['order.cart.product_categories'] = $this->helper->getProductCategories($quote);
                    $object['order.cart.product_names'] = $this->helper->getProductNames($quote);
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
                } catch (NoSuchEntityException $e) {
                    $searchCriteria = $this->searchCriteriaBuilder->addFilter(
                        'increment_id',
                        $id,
                        'eq'
                    )->create();

                    $list = $this->orderRepository->getList($searchCriteria)->getItems();
                    if (count($list) > 0) {
                        $shipment = $list[0];
                    }
                }

                if ($shipment) {
                    $object['order'] = $shipment->getOrder();
                    $object['shipment'] = $shipment;
                    $object['shipment.products'] = $this->helper->getShippingProducts($shipment);
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
                } catch (NoSuchEntityException $e) {
                    try {
                        $object['customer'] = $this->customerRepository->get($id);
                    } catch (NoSuchEntityException $e) {
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
