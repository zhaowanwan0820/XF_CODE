<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 获取视频列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author LiBing <libing10@ucfgroup.com>
 */
class ResponseGetVideoList extends ResponseBase
{
    /**
     * 视频列表数据（带翻页 ）
     *
     * @var array
     * @required
     */
    private $dataPage;

    /**
     * @return array
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param array $dataPage
     * @return ResponseGetVideoList
     */
    public function setDataPage(array $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}