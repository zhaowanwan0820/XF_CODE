<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取公募基金经理详细资料
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetFundManagerDetail extends ResponseBase
{
    /**
     * 基金经理档案
     *
     * @var array
     * @required
     */
    private $archives;

    /**
     * @return array
     */
    public function getArchives()
    {
        return $this->archives;
    }

    /**
     * @param array $archives
     * @return ResponseGetFundManagerDetail
     */
    public function setArchives(array $archives)
    {
        $this->archives = $archives;

        return $this;
    }

}