<?php
namespace Rule\RuleMailer\Plugin\Magento\Customer\Model;

use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Request\Http as Request;
use Rule\RuleMailer\Model\Api\Subscriber;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Data\Form\FormKey\Validator;

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
    protected $scopeConfig;

    /**
     * @var Subscriber
     */
    protected $subscriberApi;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var RedirectFactory
     */
    protected $redirectFactory;

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
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param callable $proceed
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param null $password
     * @param string $redirectUrl
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCreateAccount(
        \Magento\Customer\Model\AccountManagement $subject,
        callable $proceed,
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        $password = null,
        $redirectUrl = ''
    ) {
        // validate form key
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $this->logger->info("Form_Key:Invalid Form Key");
        }
        $this->logger->info("Form_Key:Valid Form Key");

        // Block if honeypot field is filled out
        if ($this->request->getPost('hpt-url')) {
            $postData = $this->request->getPost();
            $line = "-- Spam Attempt --\nPOST DATA: " . json_encode($postData) . "\n";
            $this->logger->info($line);
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
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param \Closure                                  $proceed
     * @param                                           $customerEmail
     * @param                                           $websiteId
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsEmailAvailable(
        \Magento\Customer\Model\AccountManagement $subject,
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
            if ($sendToRule) {
                // Create a temporary customer account
                $customer = $this->customerFactory->create();

                // Populate the model with the e-mail address
                $customer->setEmail($customerEmail);

                // Send the request
                $this->subscriberApi->updateCustomerCart($customer, $this->cart);
            }
        } catch (\Exception $e) {
            null;
        }

        // Return the original result
        return $originalResult;
    }
}
