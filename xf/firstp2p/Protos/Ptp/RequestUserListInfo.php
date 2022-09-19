<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 根据用户ID列表，或者手机列表获取用户信息,Open使用
 *
 * 由代码生成器生成, 不可人为修改
 * @author milining
 */
class RequestUserListInfo extends AbstractRequestBase
{
    /**
     * 用户ID列表
     *
     * @var string
     * @optional
     */
    private $userIdList = '';

    /**
     * 手机号码列表
     *
     * @var string
     * @optional
     */
    private $mobileList = '';

    /**
     * @return string
     */
    public function getUserIdList()
    {
        return $this->userIdList;
    }

    /**
     * @param string $userIdList
     * @return RequestUserListInfo
     */
    public function setUserIdList($userIdList = '')
    {
        $this->userIdList = $userIdList;

        return $this;
    }
    /**
     * @return string
     */
    public function getMobileList()
    {
        return $this->mobileList;
    }

    /**
     * @param string $mobileList
     * @return RequestUserListInfo
     */
    public function setMobileList($mobileList = '')
    {
        $this->mobileList = $mobileList;

        return $this;
    }

}