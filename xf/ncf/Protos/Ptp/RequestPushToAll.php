<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 全部用户推送
 *
 * 由代码生成器生成, 不可人为修改
 * @author longbo
 */
class RequestPushToAll extends AbstractRequestBase
{
    /**
     * 应用Id(网信理财1)
     *
     * @var int
     * @required
     */
    private $appId;

    /**
     * Title
     *
     * @var string
     * @optional
     */
    private $title = '';

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
     * @return RequestPushToAll
     */
    public function setAppId($appId)
    {
        \Assert\Assertion::integer($appId);

        $this->appId = $appId;

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
     * @return RequestPushToAll
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
     * @return RequestPushToAll
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
     * @return RequestPushToAll
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
     * @return RequestPushToAll
     */
    public function setParams(array $params = array (
))
    {
        $this->params = $params;

        return $this;
    }

}