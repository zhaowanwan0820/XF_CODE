<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 发送站内信并推送
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai
 */
class RequestMsgBoxSend extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 消息类型
     *
     * @var int
     * @required
     */
    private $type;

    /**
     * 标题
     *
     * @var string
     * @required
     */
    private $title;

    /**
     * 消息内容
     *
     * @var string
     * @required
     */
    private $content;

    /**
     * 批量用户ID
     *
     * @var array
     * @optional
     */
    private $batchUserIds = NULL;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestMsgBoxSend
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestMsgBoxSend
     */
    public function setType($type)
    {
        \Assert\Assertion::integer($type);

        $this->type = $type;

        return $this;
    }
    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return RequestMsgBoxSend
     */
    public function setTitle($title)
    {
        \Assert\Assertion::string($title);

        $this->title = $title;

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
     * @return RequestMsgBoxSend
     */
    public function setContent($content)
    {
        \Assert\Assertion::string($content);

        $this->content = $content;

        return $this;
    }
    /**
     * @return array
     */
    public function getBatchUserIds()
    {
        return $this->batchUserIds;
    }

    /**
     * @param array $batchUserIds
     * @return RequestMsgBoxSend
     */
    public function setBatchUserIds(array $batchUserIds = NULL)
    {
        $this->batchUserIds = $batchUserIds;

        return $this;
    }

}