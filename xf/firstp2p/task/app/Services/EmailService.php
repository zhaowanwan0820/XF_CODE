<?php
namespace NCFGroup\Task\Services;

use NCFGroup\Task\Events\MailEvent;

class EmailService
{
    /**
     * sendSync
     * 同步邮件发送
     *
     * @param  array $receivers   收拾人
     * @param  mixed $subject     主题
     * @param  mixed $content     邮件内容
     * @param  array $attachments 附件，{{'path'=>'xxx', 'name'=>'xxx'},{'path'=>'xxx', 'name'=>'xxx'}}
     * @access public
     * @return bool
     */
    public function sendSync(array $receivers, $subject, $content, array $attachments = array())
    {
        if(!$this->isAllowedSend($subject)) {
            return true;
        }

        foreach ($receivers as $receiver) {
            $mailEvent = new MailEvent($receiver, $subject, $content, $attachments);
            $mailEvent->execute();
        }
        return true;
    }

    public function sendAsync(array $receivers, $subject, $content)
    {
        if(!$this->isAllowedSend($subject)) {
            return true;
        }

        $taskSvc = new TaskService();
        foreach ($receivers as $receiver) {
            $mailEvent = new MailEvent($receiver, $subject, $content);
            $taskSvc->doBackground($mailEvent, 20);
        }
        return true;
    }

    /**
     * @param $subject
     * 根据主题，防止邮件重复发送。
     * @return bool
     */
    private function isAllowedSend($subject)
    {
        return (getDI()->get('frequencyHandler')->canDo('alert_' . $subject, 300));
    }
}
