<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;
use NCFGroup\Protos\Medal\ProtoMedalInfo;

/**
 * 勋章admin列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai
 */
class ResponseGetMedalListForAdmin extends ResponseBase
{
    /**
     * 勋章列表带翻页
     *
     * @var \NCFGroup\Common\Extensions\Base\Page<ProtoMedalInfo>
     * @required
     */
    private $dataPage;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page<ProtoMedalInfo>
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page<ProtoMedalInfo> $dataPage
     * @return ResponseGetMedalListForAdmin
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}