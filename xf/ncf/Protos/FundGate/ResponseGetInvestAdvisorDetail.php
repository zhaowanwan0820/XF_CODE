<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取公募基金管理人详细资料
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetInvestAdvisorDetail extends ResponseBase
{
    /**
     * 基金管理人档案
     *
     * @var array
     * @required
     */
    private $archives;

    /**
     * 基金管理人旗下管理的基金
     *
     * @var array
     * @required
     */
    private $fundList;

    /**
     * @return array
     */
    public function getArchives()
    {
        return $this->archives;
    }

    /**
     * @param array $archives
     * @return ResponseGetInvestAdvisorDetail
     */
    public function setArchives(array $archives)
    {
        $this->archives = $archives;

        return $this;
    }
    /**
     * @return array
     */
    public function getFundList()
    {
        return $this->fundList;
    }

    /**
     * @param array $fundList
     * @return ResponseGetInvestAdvisorDetail
     */
    public function setFundList(array $fundList)
    {
        $this->fundList = $fundList;

        return $this;
    }

}