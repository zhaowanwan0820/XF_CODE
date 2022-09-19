<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 投资分析数据
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ResponseSearchCustomers extends ResponseBase
{
    /**
     * 带翻页的用户列表
     *
     * @var \NCFGroup\Common\Extensions\Base\Page<ProtoSearchCustomer>
     * @optional
     */
    private $dataPage = NULL;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page<ProtoSearchCustomer>
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page<ProtoSearchCustomer> $dataPage
     * @return ResponseSearchCustomers
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage = NULL)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}