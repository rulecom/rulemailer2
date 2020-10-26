<?php
namespace Rule\RuleMailer\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\ValidatorException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MetaFields extends \Magento\Config\Block\System\Config\Form\Field
{
    /** @var \Rule\RuleMailer\Helper\Data */
    private $helper;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var string */
    private $defaults;

    /**
     * MetaFields constructor.
     * @param \Rule\RuleMailer\Helper\Data $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Module\Dir\Reader $reader
     * @param \Magento\Framework\Xml\Parser $parser
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        \Rule\RuleMailer\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Module\Dir\Reader $reader,
        \Magento\Framework\Xml\Parser $parser,
        \Psr\Log\LoggerInterface $logger,
        Context $context,
        array $data = []
    ) {
        try {
            $filePath = $reader->getModuleDir('etc', 'Rule_RuleMailer') . '/config.xml';
            $xpath = new \DOMXPath($parser->load($filePath)->getDom());
            $this->defaults = $xpath->query('/config/default/rule_rulemailer/general/meta_fields')->item(0)->nodeValue;
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
    protected function getSources()
    {
        return array_merge(
            $this->helper->getMethods(\Magento\Sales\Api\Data\OrderInterface::class, 'order'),
            $this->helper->getMethods(\Magento\Sales\Api\Data\OrderAddressInterface::class, 'order.address'),
            $this->helper->getMethods(\Magento\Quote\Api\Data\CartInterface::class, 'cart'),
            $this->helper->getMethods(\Magento\Customer\Api\Data\CustomerInterface::class, 'customer'),
            $this->helper->getMethods(\Magento\Sales\Api\Data\ShipmentInterface::class, 'shipment')
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
            $this->assign('defaults', $this->defaults);
            return $this->fetchView(
                $this->getTemplateFile('Rule_RuleMailer::meta_fields.phtml')
            );
        } catch (ValidatorException $e) {
            return $e->getMessage();
        }
    }
}
