<?php
namespace Rule\RuleMailer\Plugin\Magento\Customer\Model;

use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\AccountManagement as AccountManagementSubject;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Data\Form\FormKey\Validator;
use Rule\RuleMailer\Model\Api\Subscriber;


/**
 * Class AccountManagement implements plugin over \Magento\Customer\Model\AccountManagement class
 *
 * @author   Robert Lord, Codepeak AB <robert@codepeak.se>
 * @link     https://codepeak.se
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AccountManagement
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Subscriber
     */
    private $subscriberApi;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * AccountManagement constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Cart $cart
     * @param CustomerFactory $customerFactory
     * @param Request $request
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $redirectFactory
     * @param Logger $logger
     * @param Validator $formKeyValidator
     * @param Subscriber $subscriberApi
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Cart $cart,
        CustomerFactory $customerFactory,
        Request $request,
        ManagerInterface $messageManager,
        RedirectFactory $redirectFactory,
        Logger $logger,
        Validator $formKeyValidator,
        Subscriber $subscriberApi
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->cart = $cart;
        $this->customerFactory = $customerFactory;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->redirectFactory = $redirectFactory;
        $this->logger = $logger;
        $this->formKeyValidator = $formKeyValidator;
        $this->subscriberApi = $subscriberApi;
    }

    /**
     * @param AccountManagementSubject $subject
     * @param callable $proceed
     * @param CustomerInterface $customer
     * @param null $password
     * @param string $redirectUrl
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCreateAccount(
        AccountManagementSubject $subject,
        callable $proceed,
        CustomerInterface $customer,
        $password = null,
        $redirectUrl = ''
    ) {
        // Validate form key
        if (!$this->formKeyValidator->validate($this->request)) {
            $this->logger->info('Form_Key:Invalid Form Key');
        }

        $this->logger->info('Form_Key:Valid Form Key');

        // Block if honeypot field is filled out
        if ($this->request->getPost('hpt-url')) {
            $this->logger->info('-- Spam Attempt --', $this->request->getPost());

            $this->messageManager->addError('Invalid form data. Please try again.');

            return $this->redirectFactory->create()->setPath('customer/account/create');
        }

        // Continue with registration as normal
        return $proceed($customer, $password, $redirectUrl);
    }

    /**
     * This is started around isEmailAvailable call. We will catch the e-mail used for the field in checkout
     * to populate and data in RuleMailer, if we're supposed to.
     *
     * @param AccountManagementSubject                  $subject
     * @param \Closure                                  $proceed
     * @param                                           $customerEmail
     * @param                                           $websiteId
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsEmailAvailable(
        AccountManagementSubject $subject,
        \Closure $proceed,
        $customerEmail,
        $websiteId = null
    ) {
        // Perform the original request
        $originalResult = $proceed($customerEmail, $websiteId);

        // Let's make it safe to avoid any errors
        try {
            // Fetch setting for aggressive abandoned cart
            $aggressive = $this->scopeConfig->getValue(
                'rule_rulemailer/general/aggressive_abandoned_cart',
                ScopeInterface::SCOPE_STORE
            );

            // Default is that we don't send to Rule
            $sendToRule = false;

            // If the original result is false, then e-mail exists
            if (!$originalResult) {
                $sendToRule = true;
            } else {
                // E-mail doesn't exist, if aggressive mode is enabled, use the e-mail address
                if ($aggressive == '1') {
                    $sendToRule = true;
                }
            }

            // Check if we should send the customer to rule
            if ($sendToRule &&
                !($this->request->getModuleName() === 'checkout' &&
                  $this->request->getActionName() === 'success')
            ) {
                // Create a temporary customer account
                $customer = $this->customerFactory->create();

                // Populate the model with the e-mail address
                $customer->setEmail($customerEmail);

                // Send the request
                $this->subscriberApi->updateCustomerCart($customer, $this->cart);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        // Return the original result
        return $originalResult;
    }
}
