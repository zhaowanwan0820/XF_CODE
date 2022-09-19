<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取勋章列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author dengyi<dengyi@ucfgroup.com>
 */
class ResponseMedalList extends AbstractRequestBase
{
    /**
     * 勋章列表
     *
     * @var array
     * @required
     */
    private $list;

    /**
     * 是否显示新手专属
     *
     * @var bool
     * @optional
     */
    private $isShowBeginner = false;

    /**
     * 新手专属的APP图片URL
     *
     * @var string
     * @optional
     */
    private $appPicUrl = '';

    /**
     * 新手专属的WEB图片URL
     *
     * @var string
     * @optional
     */
    private $webPicUrl = '';

    /**
     * 新手专属剩余的时间(s)
     *
     * @var int
     * @optional
     */
    private $remainingTime = 0;

    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseMedalList
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }
    /**
     * @return bool
     */
    public function getIsShowBeginner()
    {
        return $this->isShowBeginner;
    }

    /**
     * @param bool $isShowBeginner
     * @return ResponseMedalList
     */
    public function setIsShowBeginner($isShowBeginner = false)
    {
        $this->isShowBeginner = $isShowBeginner;

        return $this;
    }
    /**
     * @return string
     */
    public function getAppPicUrl()
    {
        return $this->appPicUrl;
    }

    /**
     * @param string $appPicUrl
     * @return ResponseMedalList
     */
    public function setAppPicUrl($appPicUrl = '')
    {
        $this->appPicUrl = $appPicUrl;

        return $this;
    }
    /**
     * @return string
     */
    public function getWebPicUrl()
    {
        return $this->webPicUrl;
    }

    /**
     * @param string $webPicUrl
     * @return ResponseMedalList
     */
    public function setWebPicUrl($webPicUrl = '')
    {
        $this->webPicUrl = $webPicUrl;

        return $this;
    }
    /**
     * @return int
     */
    public function getRemainingTime()
    {
        return $this->remainingTime;
    }

    /**
     * @param int $remainingTime
     * @return ResponseMedalList
     */
    public function setRemainingTime($remainingTime = 0)
    {
        $this->remainingTime = $remainingTime;

        return $this;
    }

}