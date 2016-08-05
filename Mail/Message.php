<?php namespace Rule\RuleMailer\Mail;

use Magento\Framework\Mail\Message as MagentoMessage;

class Message extends MagentoMessage
{
    private $_fromAssoc;
    private $_recipientsAssoc = [];

    public function setFrom($email, $name = null)
    {
        $this->_fromAssoc = ['name' => $this->_filterName($name), 'email' => $this->_filterEmail($email)];

        parent::setFrom($email, $name);
    }

    public function getFromAssoc()
    {
        return $this->_fromAssoc;
    }

    protected function _addRecipientAndHeader($headerName, $email, $name)
    {
        $this->_recipientsAssoc[] = [
            'name' => $this->_filterName($name),
            'email' => $this->_filterEmail($email)
        ];

        parent::_addRecipientAndHeader($headerName, $email, $name);
    }

    public function getRecipientsAssoc()
    {
        return $this->_recipientsAssoc;
    }
}