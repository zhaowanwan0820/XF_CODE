<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 投资详细记录列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ResponseCfpLoans extends ResponseBase
{
    /**
     * 投资详细记录列表（带翻页）
     *
     * @var \NCFGroup\Common\Extensions\Base\Page<ProtoCfpLoan>
     * @required
     */
    private $dataPage;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page<ProtoCfpLoan>
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page<ProtoCfpLoan> $dataPage
     * @return ResponseCfpLoans
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}