<?php namespace Rule\RuleMailer\Mail;

use Magento\Framework\Mail\Transport as MagentoTransport;
use Magento\Framework\Mail\MessageInterface;
use Rule\ApiWrapper\ApiFactory;

use Rule\RuleMailer\Helper\Data;

class Transport extends MagentoTransport
{
    public function __construct(MessageInterface $message, Data $dataHelper)
    {
        error_log("construct transport");
        parent::__construct($message);

        $this->dataHelper = $dataHelper;
        $this->_message = $message;
        $this->transactionalApi = ApiFactory::make($this->dataHelper->getApiKey(), 'transaction');
    }

    public function sendMessage()
    {
        error_log("sending message");
        if (!$this->dataHelper->getUseTransactional()) {
            parent::send($this->_message);
        } else {
            $html = $this->_message->getBodyHtml(true);
            $plain = $this->_message->getBodyHtml(true)
                ? $this->_message->getBodyHtml(true)
                : strip_tags($html);

            $transaction = [
                'transaction_type' => 'email',
                'transaction_name' => 'some',
                'subject' => $this->_message->getSubject(),
                'from' => $this->_message->getFromAssoc(),
                'content' => [
                    'plain' => $plain,
                    'html' => $html
                ]
            ];

            foreach ($this->_message->getRecipientsAssoc() as $recipient) {
                $transaction['to'] = $recipient;
                try {
                    $this->transactionalApi->send($transaction);
                } catch (\Exception $e) {
                    error_log($e->getMessage());
                }
            }
        }
    }
}