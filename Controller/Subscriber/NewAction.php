<?php

namespace Rule\RuleMailer\Controller\Subscriber;

use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Newsletter\Model\Subscriber as SubscriberModel;
use Magento\Newsletter\Controller\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Zend_Validate;
use Magento\Framework\Validator\EmailAddress;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class NewAction extends Subscriber implements HttpPostActionInterface
{
    /**
     * @var CustomerAccountManagement
     */
    private $customerAccountManagement;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param SubscriberFactory $subscriberFactory
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param CustomerUrl $customerUrl
     * @param CustomerAccountManagement $customerAccountManagement
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        SubscriberFactory $subscriberFactory,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        CustomerUrl $customerUrl,
        CustomerAccountManagement $customerAccountManagement,
        Validator $formKeyValidator,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->customerAccountManagement = $customerAccountManagement;
        $this->formKeyValidator = $formKeyValidator;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;

        parent::__construct(
            $context,
            $subscriberFactory,
            $customerSession,
            $storeManager,
            $customerUrl
        );
    }

    /**
     * New subscription action
     *
     * @throws LocalizedException
     * @return void
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            // invalid form key
            $this->logger->info("Form_Key:Invalid Form Key");
        }

        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('email')) {
            $email = (string)$this->getRequest()->getPost('email');

            try {
                $this->validateHoneypot();
                $this->validateEmailFormat($email);
                $this->validateGuestSubscription();
                $this->validateEmailAvailable($email);

                $subscriber = $this->_subscriberFactory->create()->loadByEmail($email);
                if ($subscriber->getId()
                    && $subscriber->getSubscriberStatus() == SubscriberModel::STATUS_SUBSCRIBED
                ) {
                    throw new LocalizedException(
                        __('This email address is already subscribed.')
                    );
                }

                $status = $this->_subscriberFactory->create()->subscribe($email);
                if ($status == SubscriberModel::STATUS_NOT_ACTIVE) {
                    $this->messageManager->addSuccess(__('The confirmation request has been sent.'));
                } else {
                    $this->messageManager->addSuccess(__('Thank you for your subscription.'));
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addException(
                    $e,
                    __('There was a problem with the subscription: %1', $e->getMessage())
                );
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong with the subscription.'));
            }
        }
        $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
    }

    private function validateHoneypot()
    {
        if ($this->getRequest()->getPost('hpt-url')) {
            throw new LocalizedException(
                __('Invalid form data.')
            );
        }
    }

    /**
     * Validates that the email address isn't being used by a different account.
     *
     * @param string $email
     * @throws LocalizedException
     * @return void
     */
    private function validateEmailAvailable($email)
    {
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        if ($this->_customerSession->getCustomerDataObject()->getEmail() !== $email
            && !$this->customerAccountManagement->isEmailAvailable($email, $websiteId)
        ) {
            throw new LocalizedException(
                __('This email address is already assigned to another user.')
            );
        }
    }

    /**
     * Validates that if the current user is a guest, that they can subscribe to a newsletter.
     *
     * @throws LocalizedException
     * @return void
     */
    private function validateGuestSubscription()
    {
        if ($this->scopeConfig->getValue(SubscriberModel::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG, ScopeInterface::SCOPE_STORE) != 1
            && !$this->_customerSession->isLoggedIn()
        ) {
            throw new LocalizedException(
                __(
                    'Sorry, but the administrator denied subscription for guests. Please <a href="%1">register</a>.',
                    $this->_customerUrl->getRegisterUrl()
                )
            );
        }
    }

    /**
     * Validates the format of the email address
     *
     * @param string $email
     * @throws LocalizedException
     * @throws \Zend_Validate_Exception
     * @return void
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function validateEmailFormat($email)
    {
        if (!Zend_Validate::is($email, EmailAddress::class)) {
            throw new LocalizedException(__('Please enter a valid email address.'));
        }
    }
}
