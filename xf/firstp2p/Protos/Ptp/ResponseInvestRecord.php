<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 理财师投资记录列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangfei@
 */
class ResponseInvestRecord extends ResponseBase
{
    /**
     * 投资记录列表（带翻页）
     *
     * @var \NCFGroup\Common\Extensions\Base\Page<ProtoCfpCommission>
     * @required
     */
    private $dataPage;

    /**
     * 搜索结果佣金总和(已反+未返)
     *
     * @var string
     * @optional
     */
    private $commission = '';

    /**
     * 搜索结果投资额总和
     *
     * @var string
     * @optional
     */
    private $investAmount = '';

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page<ProtoCfpCommission>
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page<ProtoCfpCommission> $dataPage
     * @return ResponseInvestRecord
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }
    /**
     * @return string
     */
    public function getCommission()
    {
        return $this->commission;
    }

    /**
     * @param string $commission
     * @return ResponseInvestRecord
     */
    public function setCommission($commission = '')
    {
        $this->commission = $commission;

        return $this;
    }
    /**
     * @return string
     */
    public function getInvestAmount()
    {
        return $this->investAmount;
    }

    /**
     * @param string $investAmount
     * @return ResponseInvestRecord
     */
    public function setInvestAmount($investAmount = '')
    {
        $this->investAmount = $investAmount;

        return $this;
    }

}