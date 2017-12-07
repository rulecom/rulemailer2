<?php
namespace Rule\RuleMailer\Model\Api;

use Rule\ApiWrapper\ApiFactory;

class Transaction
{
    private $transactionApi;

    public function __construct($apiKey)
    {
        $this->transactionApi = ApiFactory::make($apiKey, 'transaction');
    }

    public function sendMessage($message)
    {
        $transaction = $this->buildTransaction($message);

        foreach ($message->getRecipientsAssoc() as $recipient) {
            $transaction['to'] = $recipient;

            $this->transactionApi->send($transaction);
        }
    }

    private function buildTransaction($message)
    {
        $html = $message->getBodyHtml(true);
        $plain = $message->getBodyText(true)
            ? $message->getBodyText(true)
            : strip_tags($html);

        $transaction = [
            'transaction_type' => 'email',
            'transaction_name' => 'some',
            'subject' => $message->getSubject(),
            'from' => $message->getFromAssoc(),
            'content' => [
                'plain' => $plain,
                'html' => $html
            ]
        ];

        return $transaction;
    }
}
