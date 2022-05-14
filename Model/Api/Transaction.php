<?php

namespace Rule\RuleMailer\Model\Api;

use Rule\ApiWrapper\ApiFactory;
use Rule\ApiWrapper\Api\Api;
use Magento\Framework\Mail\Address;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MailMessageInterface;
use Magento\Framework\Mail\EmailMessageInterface;

/**
 * Class Transaction holds transaction during API calls
 */
class Transaction
{
    /**
     * @var Api
     */
    private $transactionApi;

    /**
     * Transaction constructor.
     * @param $apiKey
     * @throws \Rule\ApiWrapper\Api\Exception\InvalidResourceException
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct($apiKey)
    {
        $this->transactionApi = ApiFactory::make($apiKey, 'transaction');
    }

    /**
     * @param MessageInterface|MailMessageInterface|EmailMessageInterface $message
     * @see https://apidoc.rule.se/#transactions-send-transaction
     */
    public function sendMessage($message)
    {
        $transaction = [];

        if ($message instanceof EmailMessageInterface) {
            /** @var \Magento\Framework\Mail\EmailMessage $message */

            $from = $message->getFrom();
            $from1 = array_shift($from);

            $recipients = $message->getTo();
            foreach ($recipients as $recipient) {
                /** @var Address $item */
                $transaction = [
                    'transaction_type' => 'email',
                    'transaction_name' => 'some',
                    'subject' => $message->getSubject(),
                    'from' => [
                        'name' => $from1->getName(),
                        'email' => $from1->getEmail()
                    ],
                    'to' => [
                        'name' => $recipient->getName(),
                        'email' => $recipient->getEmail()
                    ],
                    'content' => [
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
                        'plain' => base64_encode($message->getBodyText()),
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
                        'html' => base64_encode($message->getBodyText())
                    ]
                ];


            }
        }

        if ($message instanceof MailMessageInterface) {
            // @todo
        } elseif ($message instanceof MessageInterface) {
            /** @var MessageInterface $message */
            // @todo
        }

        $this->transactionApi->send($transaction);
    }
}
