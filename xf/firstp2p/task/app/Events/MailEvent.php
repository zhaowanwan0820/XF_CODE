<?php
namespace NCFGroup\Task\Events;

use NCFGroup\Task\Instrument\MailSendCloud;

class MailEvent implements AsyncEvent
{
    private $receiver;
    private $subject;
    private $content;
    private $attachments;

    public function __construct($receiver, $subject, $content, array $attachments = array())
    {
        $this->receiver = $receiver;
        $this->subject = $subject;
        $this->content = $content;
        $this->attachments = $attachments;
    }

    public function execute()
    {
        $mailSendCloud = new MailSendCloud();
        return $mailSendCloud->send($this->subject, $this->content, $this->receiver, $this->attachments);
    }

    public function alertMails()
    {
        return array(
            'liaoyebin@ucfgroup.com', 'quanhengzhuang@ucfgroup.com',
        );
    }
}
