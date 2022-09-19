<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 用户ReqeustUpdate
 *
 * 由代码生成器生成, 不可人为修改
 * @author longbo
 */
class RequestUserUpdate extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 分站
     *
     * @var int
     * @required
     */
    private $siteId;

    /**
     * 更新数据
     *
     * @var array
     * @required
     */
    private $updateData;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestUserUpdate
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
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return RequestUserUpdate
     */
    public function setSiteId($siteId)
    {
        \Assert\Assertion::integer($siteId);

        $this->siteId = $siteId;

        return $this;
    }
    /**
     * @return array
     */
    public function getUpdateData()
    {
        return $this->updateData;
    }

    /**
     * @param array $updateData
     * @return RequestUserUpdate
     */
    public function setUpdateData(array $updateData)
    {
        $this->updateData = $updateData;

        return $this;
    }

}