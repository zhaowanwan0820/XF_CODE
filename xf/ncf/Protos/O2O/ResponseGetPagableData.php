<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 通用的获取列表数据响应接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author <yanbingrong@ucfgroup.com>
 */
class ResponseGetPagableData extends ResponseBase
{
    /**
     * 零售商供应商关系列表数据（带翻页）
     *
     * @var \NCFGroup\Common\Extensions\Base\Page<ProtoItem>
     * @required
     */
    private $dataPage;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page<ProtoItem>
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page<ProtoItem> $dataPage
     * @return ResponseGetPagableData
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}