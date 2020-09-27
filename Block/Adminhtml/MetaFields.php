<?php
namespace Rule\RuleMailer\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class MetaFields extends \Magento\Config\Block\System\Config\Form\Field
{
    /** @var \Rule\RuleMailer\Helper\Data */
    private $helper;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /**
     * MetaFields constructor.
     * @param \Rule\RuleMailer\Helper\Data $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Context $context
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        \Rule\RuleMailer\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Context $context,
        array $data = [],
        SecureHtmlRenderer $secureRenderer = null
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data, $secureRenderer);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns elements
     * @return array
     */
    protected function getSources()
    {
        return array_merge(
            $this->helper->getMethods(\Magento\Sales\Api\Data\OrderInterface::class, 'order'),
            $this->helper->getMethods(\Magento\Sales\Api\Data\OrderAddressInterface::class, 'order.address'),
            $this->helper->getMethods(\Magento\Quote\Api\Data\CartInterface::class, 'cart'),
            $this->helper->getMethods(\Magento\Customer\Api\Data\CustomerInterface::class, 'customer')
        );
    }

    /**
     * Retrieve element HTML markup
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @codingStandardsIgnoreStart
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        try {
            // $value = $this->getConfigData('rule_rulemailer/general/meta_fields');
            $value = $this->scopeConfig->getValue('rule_rulemailer/general/meta_fields');

            $this->assign('sources', $this->getSources());
            $this->assign('element', $element);
            $this->assign('value', $value);
            return $this->fetchView(
                $this->getTemplateFile('Rule_RuleMailer::meta_fields.phtml')
            );
        } catch (ValidatorException $e) {
            return $e->getMessage();
        }
    }
}
