<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 设置用户Tag
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhaohui
 */
class RequestUserTags extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @optional
     */
    private $userId = NULL;

    /**
     * Tag对应的id
     *
     * @var array
     * @optional
     */
    private $tagIds = NULL;

    /**
     * 标签名称
     *
     * @var array
     * @optional
     */
    private $constName = NULL;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestUserTags
     */
    public function setUserId($userId = NULL)
    {
        $this->userId = $userId;

        return $this;
    }
    /**
     * @return array
     */
    public function getTagIds()
    {
        return $this->tagIds;
    }

    /**
     * @param array $tagIds
     * @return RequestUserTags
     */
    public function setTagIds(array $tagIds = NULL)
    {
        $this->tagIds = $tagIds;

        return $this;
    }
    /**
     * @return array
     */
    public function getConstName()
    {
        return $this->constName;
    }

    /**
     * @param array $constName
     * @return RequestUserTags
     */
    public function setConstName(array $constName = NULL)
    {
        $this->constName = $constName;

        return $this;
    }

}