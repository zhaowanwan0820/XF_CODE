<?php
namespace NCFGroup\Protos\Open;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * open:发送短信
 *
 * 由代码生成器生成, 不可人为修改
 * @author liuzhenpeng
 */
class ProtoSendSms extends ProtoBufferBase
{
    /**
     * 手机号,多个使用(,)逗号分隔
     *
     * @var string
     * @optional
     */
    private $mobiles = '';

    /**
     * 短信内容,多个使用(,)逗号分隔,跟mobiles一一对应
     *
     * @var string
     * @optional
     */
    private $messages = '';

    /**
     * 日志文案
     *
     * @var string
     * @optional
     */
    private $logsTitle = '';

    /**
     * @return string
     */
    public function getMobiles()
    {
        return $this->mobiles;
    }

    /**
     * @param string $mobiles
     * @return ProtoSendSms
     */
    public function setMobiles($mobiles = '')
    {
        $this->mobiles = $mobiles;

        return $this;
    }
    /**
     * @return string
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param string $messages
     * @return ProtoSendSms
     */
    public function setMessages($messages = '')
    {
        $this->messages = $messages;

        return $this;
    }
    /**
     * @return string
     */
    public function getLogsTitle()
    {
        return $this->logsTitle;
    }

    /**
     * @param string $logsTitle
     * @return ProtoSendSms
     */
    public function setLogsTitle($logsTitle = '')
    {
        $this->logsTitle = $logsTitle;

        return $this;
    }

}