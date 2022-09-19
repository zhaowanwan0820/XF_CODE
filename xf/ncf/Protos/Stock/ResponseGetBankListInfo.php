<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取银行列表信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class ResponseGetBankListInfo extends ResponseBase
{
    /**
     * 初始化信息
     *
     * @var array
     * @optional
     */
    private $initData = NULL;

    /**
     * 用户信息
     *
     * @var array
     * @optional
     */
    private $userData = NULL;

    /**
     * @return array
     */
    public function getInitData()
    {
        return $this->initData;
    }

    /**
     * @param array $initData
     * @return ResponseGetBankListInfo
     */
    public function setInitData(array $initData = NULL)
    {
        $this->initData = $initData;

        return $this;
    }
    /**
     * @return array
     */
    public function getUserData()
    {
        return $this->userData;
    }

    /**
     * @param array $userData
     * @return ResponseGetBankListInfo
     */
    public function setUserData(array $userData = NULL)
    {
        $this->userData = $userData;

        return $this;
    }

}