<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 是否登记过移动设备
 *
 * 由代码生成器生成, 不可人为修改
 * @author quanhengzhuang
 */
class RequestPushIsSigned extends AbstractRequestBase
{
    /**
     * 应用Id(网信理财1)
     *
     * @var int
     * @required
     */
    private $appId;

    /**
     * 应用userId
     *
     * @var int
     * @required
     */
    private $appUserId;

    /**
     * @return int
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param int $appId
     * @return RequestPushIsSigned
     */
    public function setAppId($appId)
    {
        \Assert\Assertion::integer($appId);

        $this->appId = $appId;

        return $this;
    }
    /**
     * @return int
     */
    public function getAppUserId()
    {
        return $this->appUserId;
    }

    /**
     * @param int $appUserId
     * @return RequestPushIsSigned
     */
    public function setAppUserId($appUserId)
    {
        \Assert\Assertion::integer($appUserId);

        $this->appUserId = $appUserId;

        return $this;
    }

}