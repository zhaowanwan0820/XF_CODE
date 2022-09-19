<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 用户设备签入接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author quanhengzhuang
 */
class RequestPushSignIn extends AbstractRequestBase
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
     * 客户端版本(3.0)
     *
     * @var string
     * @required
     */
    private $appVersion;

    /**
     * 百度UserId
     *
     * @var string
     * @required
     */
    private $baiduUserId;

    /**
     * 百度ChannelId
     *
     * @var string
     * @required
     */
    private $baiduChannelId;

    /**
     * 系统类型(IOS/Android)
     *
     * @var int
     * @required
     */
    private $osType;

    /**
     * 系统版本(8.1.3)
     *
     * @var string
     * @required
     */
    private $osVersion;

    /**
     * 设备型号(iPad2/iPhone6/Xiaomi4)
     *
     * @var string
     * @required
     */
    private $model;

    /**
     * Apple推送Token
     *
     * @var string
     * @optional
     */
    private $apnsToken = '';

    /**
     * @return int
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param int $appId
     * @return RequestPushSignIn
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
     * @return RequestPushSignIn
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
    public function getAppVersion()
    {
        return $this->appVersion;
    }

    /**
     * @param string $appVersion
     * @return RequestPushSignIn
     */
    public function setAppVersion($appVersion)
    {
        \Assert\Assertion::string($appVersion);

        $this->appVersion = $appVersion;

        return $this;
    }
    /**
     * @return string
     */
    public function getBaiduUserId()
    {
        return $this->baiduUserId;
    }

    /**
     * @param string $baiduUserId
     * @return RequestPushSignIn
     */
    public function setBaiduUserId($baiduUserId)
    {
        \Assert\Assertion::string($baiduUserId);

        $this->baiduUserId = $baiduUserId;

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
     * @return RequestPushSignIn
     */
    public function setBaiduChannelId($baiduChannelId)
    {
        \Assert\Assertion::string($baiduChannelId);

        $this->baiduChannelId = $baiduChannelId;

        return $this;
    }
    /**
     * @return int
     */
    public function getOsType()
    {
        return $this->osType;
    }

    /**
     * @param int $osType
     * @return RequestPushSignIn
     */
    public function setOsType($osType)
    {
        \Assert\Assertion::integer($osType);

        $this->osType = $osType;

        return $this;
    }
    /**
     * @return string
     */
    public function getOsVersion()
    {
        return $this->osVersion;
    }

    /**
     * @param string $osVersion
     * @return RequestPushSignIn
     */
    public function setOsVersion($osVersion)
    {
        \Assert\Assertion::string($osVersion);

        $this->osVersion = $osVersion;

        return $this;
    }
    /**
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param string $model
     * @return RequestPushSignIn
     */
    public function setModel($model)
    {
        \Assert\Assertion::string($model);

        $this->model = $model;

        return $this;
    }
    /**
     * @return string
     */
    public function getApnsToken()
    {
        return $this->apnsToken;
    }

    /**
     * @param string $apnsToken
     * @return RequestPushSignIn
     */
    public function setApnsToken($apnsToken = '')
    {
        $this->apnsToken = $apnsToken;

        return $this;
    }

}