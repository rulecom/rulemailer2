<?php namespace Rule\RuleMailer\Mail;

use Psr\Log\LoggerInterface;
use Magento\Framework\Mail\Transport as MagentoTransport;
use Magento\Framework\Mail\MessageInterface;
use Rule\ApiWrapper\ApiFactory;
use Rule\RuleMailer\Helper\Data;
use Rule\RuleMailer\Model\Api\Transaction;

class Transport extends MagentoTransport
{
    private $logger;

    public function __construct(MessageInterface $message, Data $dataHelper, LoggerInterface $logger)
    {
        parent::__construct($message);

        $this->dataHelper = $dataHelper;
        $this->_message = $message;
        $this->logger = $logger;
        $this->transactionalApi = new Transaction($this->dataHelper->getApiKey());
    }

    public function sendMessage()
    {
        if (!$this->dataHelper->getUseTransactional()) {
            try {
                parent::send($this->_message);
            } catch (\Exception $e) {
                $this->logger->info("Failed to send message: " . $e->getMessage());
            }
        } else {
            try {
                $this->transactionalApi->sendMessage($this->_message);
            } catch (\Exception $e) {
                $this->logger->info("Failed to send message: " . $e->getMessage());
            }
        }
    }
}