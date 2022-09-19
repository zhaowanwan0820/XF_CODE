<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取热门基金列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetHotFunds extends ResponseBase
{
    /**
     * 基金列表数据（带翻页）
     *
     * @var \NCFGroup\Common\Extensions\Base\Page
     * @required
     */
    private $dataPage;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page $dataPage
     * @return ResponseGetHotFunds
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}