<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 单个用户推送接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author quanhengzhuang
 */
class RequestPushToSingle extends AbstractRequestBase
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
     * 消息内容
     *
     * @var string
     * @required
     */
    private $content;

    /**
     * 角标
     *
     * @var int
     * @optional
     */
    private $badge = 0;

    /**
     * 附加参数
     *
     * @var array
     * @optional
     */
    private $params = array (
);

    /**
     * @return int
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param int $appId
     * @return RequestPushToSingle
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
     * @return RequestPushToSingle
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
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return RequestPushToSingle
     */
    public function setContent($content)
    {
        \Assert\Assertion::string($content);

        $this->content = $content;

        return $this;
    }
    /**
     * @return int
     */
    public function getBadge()
    {
        return $this->badge;
    }

    /**
     * @param int $badge
     * @return RequestPushToSingle
     */
    public function setBadge($badge = 0)
    {
        $this->badge = $badge;

        return $this;
    }
    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     * @return RequestPushToSingle
     */
    public function setParams(array $params = array (
))
    {
        $this->params = $params;

        return $this;
    }

}