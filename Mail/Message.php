<?php namespace Rule\RuleMailer\Mail;

use Magento\Framework\Mail\Message as MagentoMessage;

/**
 * Class Message preference for \Magento\Framework\Mail\Message class
 */
class Message extends MagentoMessage
{
    /**
     * @var
     * @codingStandardsIgnoreStart
     */
    private $_fromAssoc;

    /**
     * @var array
     * @codingStandardsIgnoreStart
     */
    private $_recipientsAssoc = [];

    /**
     * @param $email
     * @param null $name
     * @return MagentoMessage|void
     */
    public function setFrom($email, $name = null)
    {
        $this->_fromAssoc = ['name' => $this->_filterName($name), 'email' => $this->_filterEmail($email)];

        parent::setFrom($email, $name);
    }

    /**
     * @return mixed
     */
    public function getFromAssoc()
    {
        return $this->_fromAssoc;
    }

    /**
     * @param $headerName
     * @param $email
     * @param $name
     * @codingStandardsIgnoreStart
     */
    protected function _addRecipientAndHeader($headerName, $email, $name)
    {
        $this->_recipientsAssoc[] = [
            'name' => $this->_filterName($name),
            'email' => $this->_filterEmail($email)
        ];

        parent::_addRecipientAndHeader($headerName, $email, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipientsAssoc()
    {
        return $this->_recipientsAssoc;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
