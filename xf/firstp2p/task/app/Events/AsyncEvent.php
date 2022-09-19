<?php
namespace NCFGroup\Task\Events;

interface AsyncEvent
{
    /**
     * execute 执行内容
     * 注:返回值必须为bool
     *
     * @access public
     * @return bool
     */
    public function execute();

    /**
     * alertMails 报警邮件
     *
     * @access public
     * @return array
     */
    public function alertMails();
}
