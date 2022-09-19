<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 开发放平台服务
 *
 * 由代码生成器生成, 不可人为修改
 * @author milining
 */
class RequestSmsg extends ProtoBufferBase
{
    /**
     * 用户id组
     *
     * @var array
     * @required
     */
    private $userIds;

    /**
     * 发送标题
     *
     * @var string
     * @optional
     */
    private $title = '';

    /**
     * 发送内容
     *
     * @var string
     * @required
     */
    private $content;

    /**
     * @return array
     */
    public function getUserIds()
    {
        return $this->userIds;
    }

    /**
     * @param array $userIds
     * @return RequestSmsg
     */
    public function setUserIds(array $userIds)
    {
        $this->userIds = $userIds;

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
     * @return RequestSmsg
     */
    public function setTitle($title = '')
    {
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
     * @return RequestSmsg
     */
    public function setContent($content)
    {
        \Assert\Assertion::string($content);

        $this->content = $content;

        return $this;
    }

}