<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 获取登录用户信息接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ResponseGetLoginUser extends ResponseBase
{
    /**
     * 用户信息
     *
     * @var array
     * @required
     */
    private $user;

    /**
     * @return array
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param array $user
     * @return ResponseGetLoginUser
     */
    public function setUser(array $user)
    {
        $this->user = $user;

        return $this;
    }

}