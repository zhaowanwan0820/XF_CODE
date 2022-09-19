<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取订单列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class ResponseGetOrdersList extends ResponseBase
{
    /**
     * 数据（带翻页）
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
     * @return ResponseGetOrdersList
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}