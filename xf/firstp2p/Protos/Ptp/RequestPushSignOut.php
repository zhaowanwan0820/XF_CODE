<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 用户设备签出接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author quanhengzhuang
 */
class RequestPushSignOut extends AbstractRequestBase
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
     * 百度ChannelId
     *
     * @var string
     * @required
     */
    private $baiduChannelId;

    /**
     * @return int
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param int $appId
     * @return RequestPushSignOut
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
     * @return RequestPushSignOut
     */
    public function setAppUserId($appUserId)
    {
        \Assert\Assertion::integer($appUserId);

        $this->appUserId = $appUserId;

        return $this;
    }
    /**
     * @return string
     */
    public function getBaiduChannelId()
    {
        return $this->baiduChannelId;
    }

    /**
     * @param string $baiduChannelId
     * @return RequestPushSignOut
     */
    public function setBaiduChannelId($baiduChannelId)
    {
        \Assert\Assertion::string($baiduChannelId);

        $this->baiduChannelId = $baiduChannelId;

        return $this;
    }

}