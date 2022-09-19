<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

/**
 * 协议
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ProtoSendEmail extends ProtoBufferBase
{
    /**
     * 接收者
     *
     * @var array
     * @required
     */
    private $receivers;

    /**
     * 主题
     *
     * @var string
     * @required
     */
    private $subject;

    /**
     * 邮件内容
     *
     * @var string
     * @required
     */
    private $content;

    /**
     * 附件
     *
     * @var ProtoEmailAttachment
     * @optional
     */
    private $attachment = NULL;

    /**
     * 是否是html
     *
     * @var bool
     * @optional
     */
    private $isHtml = false;

    /**
     * @return array
     */
    public function getReceivers()
    {
        return $this->receivers;
    }

    /**
     * @param array $receivers
     * @return ProtoSendEmail
     */
    public function setReceivers(array $receivers)
    {
        $this->receivers = $receivers;

        return $this;
    }
    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     * @return ProtoSendEmail
     */
    public function setSubject($subject)
    {
        \Assert\Assertion::string($subject);

        $this->subject = $subject;

        return $this;
    }
    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return ProtoSendEmail
     */
    public function setContent($content)
    {
        \Assert\Assertion::string($content);

        $this->content = $content;

        return $this;
    }
    /**
     * @return ProtoEmailAttachment
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @param ProtoEmailAttachment $attachment
     * @return ProtoSendEmail
     */
    public function setAttachment(ProtoEmailAttachment $attachment = NULL)
    {
        $this->attachment = $attachment;

        return $this;
    }
    /**
     * @return bool
     */
    public function getIsHtml()
    {
        return $this->isHtml;
    }

    /**
     * @param bool $isHtml
     * @return ProtoSendEmail
     */
    public function setIsHtml($isHtml = false)
    {
        $this->isHtml = $isHtml;

        return $this;
    }

}