<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取基金专题信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetTopicInfo extends ResponseBase
{
    /**
     * 基金专题信息
     *
     * @var array
     * @required
     */
    private $info;

    /**
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param array $info
     * @return ResponseGetTopicInfo
     */
    public function setInfo(array $info)
    {
        $this->info = $info;

        return $this;
    }

}