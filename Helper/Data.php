<?php namespace Rule\RuleMailer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function getApiKey($store_id = null)
    {
        return $this->scopeConfig->getValue('rule_rulemailer/general/api_key',
            ScopeInterface::SCOPE_STORE, $store_id);
    }

    public function getUseTransactional($store_id = null)
    {
        return $this->scopeConfig->getValue('rule_rulemailer/general/use_transactional',
            ScopeInterface::SCOPE_STORE, $store_id);
    }
}