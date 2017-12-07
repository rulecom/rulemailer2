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

    /**
     * Return Zend_Mime_Part representing body HTML
     *
     * @param  bool $htmlOnly Whether to return the body HTML only, or the MIME part; defaults to false, the MIME part
     * @return false|Zend_Mime_Part|string
     */
    public function getBodyHtml($htmlOnly = false)
    {
        if ($htmlOnly && $this->_bodyHtml) {
            $body = $this->_bodyHtml;
            return $body->getRawContent();
        }

        return $this->_bodyHtml;
    }

    /**
     * Return text body Zend_Mime_Part or string
     *
     * @param  bool textOnly Whether to return just the body text content or the MIME part; defaults to false, the MIME part
     * @return false|Zend_Mime_Part|string
     */
    public function getBodyText($textOnly = false)
    {
        if ($textOnly && $this->_bodyText) {
            $body = $this->_bodyText;
            return $body->getRawContent();
        }

        return $this->_bodyText;
    }
}
