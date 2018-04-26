<?php

namespace Rule\RuleMailer\Plugin\Magento\Customer\Model;

/**
 * Class AccountManagement
 *
 * @package  Rule\RuleMailer\Plugin\Magento\Customer\Model
 * @author   Robert Lord, Codepeak AB <robert@codepeak.se>
 * @link     https://codepeak.se
 */
class AccountManagement
{
    /**
     * This is started after isEmailAvailable call. We will catch the e-mail used for the field in checkout
     * to populate and data in RuleMailer.
     *
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param mixed                                     $result
     *
     * @return mixed
     */
    public function aroundIsEmailAvailable(
        \Magento\Customer\Model\AccountManagement $subject,
        \Closure $proceed,
        $customerEmail,
        $websiteId
    ) {
        // Let's make it safe to avoid any errors
        try {
            // Let's do our magic here
        } catch (\Exception $e) {
            // Do nothing, silently continue with normal operations
        }

        // Return the orginal result
        return $result;
    }
}