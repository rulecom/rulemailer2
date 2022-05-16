<?php

namespace Rule\RuleMailer\Mail;

use Exception;
use Closure;
use ReflectionClass;
use Rule\RuleMailer\Helper\Data as Helper;
use Rule\RuleMailer\Model\Api\Transaction;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Phrase;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MailMessageInterface;
use Magento\Framework\Mail\EmailMessageInterface;
use Psr\Log\LoggerInterface;

class Transport
{
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Transport Constructor.
     *
     * @param ProductMetadataInterface $productMetadata
     * @param Helper                   $helper
     * @param LoggerInterface          $logger
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        Helper $helper,
        LoggerInterface $logger
    ) {
        $this->productMetadata = $productMetadata;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * @param TransportInterface $subject
     * @param Closure $proceed
     *
     * @throws MailException
     */
    public function aroundSendMessage(
        TransportInterface $subject,
        Closure $proceed
    ) {
        if (!$this->helper->getUseTransactional()) {
            $proceed();

            return;
        }

        /** @var \Magento\Framework\Mail\MessageInterface $message */
        $messageTmp = $this->getMessage($subject);

        try {
            $transactional = new Transaction($this->helper->getApiKey());
            $transactional->sendMessage($messageTmp);
        } catch (\Exception $e) {
            $this->logger->info('Failed to send message: ' . $e->getMessage());

            throw new MailException(new Phrase($e->getMessage()), $e);
        }
    }

    /**
     * @param TransportInterface $transport
     *
     * @return EmailMessageInterface|null
     */
    private function getMessage(TransportInterface $transport)
    {
        if (version_compare($this->productMetadata->getVersion(), '2.2.0', '>=')) {
            /** @return \Magento\Framework\Mail\EmailMessageInterface */
            return $transport->getMessage();
        }

        try {
            $reflectionClass = new ReflectionClass($transport);
            $message = $reflectionClass->getProperty('_message');
        } catch (Exception $e) {
            return null;
        }

        $message->setAccessible(true);

        return $message->getValue($transport);
    }
}
