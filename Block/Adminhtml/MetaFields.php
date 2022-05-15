<?php

namespace Rule\RuleMailer\Block\Adminhtml;

use DOMXPath;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\ValidatorException;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Xml\Parser;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Psr\Log\LoggerInterface;
use Rule\RuleMailer\Helper\Data as Helper;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MetaFields extends Field
{
    /** @var Helper */
    private $helper;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var string */
    private $defaults;

    /**
     * MetaFields constructor.
     * @param Helper $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param Reader $reader
     * @param Parser $parser
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Helper $helper,
        ScopeConfigInterface $scopeConfig,
        Reader $reader,
        Parser $parser,
        LoggerInterface $logger,
        Context $context,
        array $data = []
    ) {
        try {
            $filePath = $reader->getModuleDir('etc', 'Rule_RuleMailer') . '/config.xml';
            $xpath = new DOMXPath($parser->load($filePath)->getDom());
            $this->defaults = $xpath->query(
                '/config/default/rule_rulemailer/general/meta_fields'
            )->item(0)->nodeValue;
        } catch (\Exception $e) {
            $logger->error($e);
        }

        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;

        parent::__construct($context, $data);
    }

    /**
     * Returns elements
     * @return array
     */
    private function getSources()
    {
        return array_merge(
            $this->helper->getMethods(OrderInterface::class, 'order'),
            $this->helper->getMethods(OrderAddressInterface::class, 'order.address'),
            $this->helper->getMethods(CartInterface::class, 'cart'),
            $this->helper->getMethods(CustomerInterface::class, 'customer'),
            $this->helper->getMethods(ShipmentInterface::class, 'shipment')
        );
    }

    /**
     * Retrieve element HTML markup
     * @param AbstractElement $element
     * @return string
     * @codingStandardsIgnoreStart
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        try {
            // $value = $this->getConfigData('rule_rulemailer/general/meta_fields');
            $value = $this->scopeConfig->getValue('rule_rulemailer/general/meta_fields');

            $this->assign('sources', $this->getSources());
            $this->assign('element', $element);
            $this->assign('value', $value);
            $this->assign('defaults', $this->defaults);
            return $this->fetchView(
                $this->getTemplateFile('Rule_RuleMailer::meta_fields.phtml')
            );
        } catch (ValidatorException $e) {
            $this->_logger->critical($e);
        }

        return '';
    }
}
