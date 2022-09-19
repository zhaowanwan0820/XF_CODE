<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;
use NCFGroup\Protos\O2O\ProtoStoreTagConf;

/**
 * 零售店配置列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Vincent <daiyuxin@ucfgroup.com>
 */
class ResponseGetStoreTagConfList extends ResponseBase
{
    /**
     * 零售店列表数据（带翻页）
     *
     * @var \NCFGroup\Common\Extensions\Base\Page<ProtoStoreTagConf>
     * @required
     */
    private $dataPage;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page<ProtoStoreTagConf>
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page<ProtoStoreTagConf> $dataPage
     * @return ResponseGetStoreTagConfList
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}