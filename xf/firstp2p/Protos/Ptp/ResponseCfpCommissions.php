<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 理财师佣金列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong@
 */
class ResponseCfpCommissions extends ResponseBase
{
    /**
     * 佣金列表（带翻页）
     *
     * @var \NCFGroup\Common\Extensions\Base\Page<ProtoCfpCommission>
     * @required
     */
    private $dataPage;

    /**
     * 搜索结果已返佣金总和
     *
     * @var string
     * @optional
     */
    private $beenSettled = '';

    /**
     * 搜索结果未返佣金总和
     *
     * @var string
     * @optional
     */
    private $tobeSettled = '';

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page<ProtoCfpCommission>
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page<ProtoCfpCommission> $dataPage
     * @return ResponseCfpCommissions
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }
    /**
     * @return string
     */
    public function getBeenSettled()
    {
        return $this->beenSettled;
    }

    /**
     * @param string $beenSettled
     * @return ResponseCfpCommissions
     */
    public function setBeenSettled($beenSettled = '')
    {
        $this->beenSettled = $beenSettled;

        return $this;
    }
    /**
     * @return string
     */
    public function getTobeSettled()
    {
        return $this->tobeSettled;
    }

    /**
     * @param string $tobeSettled
     * @return ResponseCfpCommissions
     */
    public function setTobeSettled($tobeSettled = '')
    {
        $this->tobeSettled = $tobeSettled;

        return $this;
    }

}